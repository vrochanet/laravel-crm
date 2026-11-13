<?php

namespace Webkul\Prospeccao\Services;

use Carbon\Carbon;
use Webkul\Prospeccao\Models\Conversation;
use Webkul\Prospeccao\Models\Message;

/**
 * Avalia se uma comunicação pode ser realizada e determina
 * quando poderá ocorrer caso existam restrições temporárias.
 *
 * Centraliza as regras de decisão aplicáveis aos canais WhatsApp
 * e Email, incluindo bloqueios, pausas, limites diários, intervalos
 * entre mensagens, número máximo de seguimentos e horários permitidos.
 *
 * Este serviço apenas avalia e recomenda uma decisão.
 * Não cria, aprova nem envia mensagens.
 */
class CommunicationDecisionService
{
    /**
     * Inicializa o serviço com as configurações de comunicação.
     *
     * @param CommunicationConfigService $communicationConfig Configurações por canal.
     * @param GlobalCommunicationConfigService $globalConfig Configurações globais.
     */
    public function __construct(
        protected CommunicationConfigService $communicationConfig,
        protected GlobalCommunicationConfigService $globalConfig
    ) {
    }

    /**
     * Avalia as condições atuais de comunicação de uma conversação.
     *
     * Aplica primeiro os bloqueios absolutos e, posteriormente,
     * verifica as restrições temporárias. Quando existem várias
     * restrições temporárias, determina a data em que todas estarão
     * satisfeitas.
     *
     * @param Conversation $conversation Conversação a avaliar.
     *
     * @return array{
     *     decision: string,
     *     allowed: bool,
     *     reason: string,
     *     reasons: array,
     *     next_action_at: ?Carbon,
     *     mode: string
     * }
     */
    public function evaluate(Conversation $conversation): array
    {
        $channelSettings = $this->communicationConfig->get(
            $conversation->channel
        );

        $mode = $conversation->mode_override
            ?? $channelSettings['mode'];

        $result = [
            'decision' => 'allowed',
            'allowed' => true,
            'reason' => 'within_allowed_hours',
            'reasons' => [],
            'next_action_at' => null,
            'mode' => $mode,
        ];

        $reasons = [];

        $hasContactRestriction = $conversation->property
            ->contactRestrictions()
            ->active()
            ->forChannel($conversation->channel)
            ->exists();

        if ($hasContactRestriction) {
            $reasons[] = 'contact_blocked';
        }

        if (!$channelSettings['enabled']) {
            $reasons[] = 'channel_disabled';
        }

        if ($conversation->status === Conversation::STATUS_CLOSED) {
            $reasons[] = 'conversation_closed';
        }

        if ($reasons !== []) {
            $result['decision'] = 'blocked';
            $result['allowed'] = false;
            $result['reason'] = $reasons[0];
            $result['reasons'] = $reasons;

            return $result;
        }

        if ($conversation->status === Conversation::STATUS_PAUSED) {
            $result['allowed'] = false;

            if ($conversation->resume_at === null) {
                $result['decision'] = 'blocked';
                $result['reason'] = 'conversation_paused';
                $result['reasons'] = ['conversation_paused'];

                return $result;
            }

            $result['decision'] = 'deferred';
            $result['reason'] = 'scheduled_pause';
            $result['reasons'] = ['scheduled_pause'];
            $result['next_action_at'] = $conversation->resume_at;

            return $result;
        }

        if ($this->hasReachedMaximumFollowUps($conversation, $channelSettings)) {
            $result['decision'] = 'blocked';
            $result['allowed'] = false;
            $result['reason'] = 'maximum_follow_ups_reached';
            $result['reasons'] = ['maximum_follow_ups_reached'];

            return $result;
        }

        $globalSettings = $this->globalConfig->get();

        $readyAt = [];

        $dailyLimit = $globalSettings['max_first_contacts_per_day'];

        if (
            $dailyLimit > 0
            && $this->countFirstContactsToday() >= $dailyLimit
            && !$this->hasFirstOutgoingMessage($conversation)
        ) {
            $startTime = $conversation->start_time_override
                ?? $channelSettings['start_time'];

            $reasons[] = 'daily_limit_reached';
            $readyAt[] = $this->nextBusinessDayAt($startTime);
        }

        $minimumIntervalReadyAt = $this->minimumIntervalReadyAt(
            $conversation,
            $channelSettings
        );

        if ($minimumIntervalReadyAt !== null) {
            $reasons[] = 'minimum_interval_not_reached';
            $readyAt[] = $minimumIntervalReadyAt;
        }

        $followUpIntervalReadyAt = $this->followUpIntervalReadyAt(
            $conversation,
            $channelSettings
        );

        if ($followUpIntervalReadyAt !== null) {
            $reasons[] = 'follow_up_interval_not_reached';
            $readyAt[] = $followUpIntervalReadyAt;
        }

        $allowedHoursReadyAt = $this->allowedHoursReadyAt(
            $conversation,
            $channelSettings
        );

        if ($allowedHoursReadyAt !== null) {
            $reasons[] = 'outside_allowed_hours';
            $readyAt[] = $allowedHoursReadyAt;
        }

        if ($reasons !== []) {
            $nextActionAt = collect($readyAt)
                ->sortByDesc(fn(Carbon $date) => $date->timestamp)
                ->first();

            if ($nextActionAt !== null) {
                $nextActionAt = $this->normalizeToAllowedPeriod(
                    $nextActionAt,
                    $conversation,
                    $channelSettings
                );
            }

            $result['decision'] = 'deferred';
            $result['allowed'] = false;
            $result['reason'] = $reasons[0];
            $result['reasons'] = $reasons;
            $result['next_action_at'] = $nextActionAt;

            return $result;
        }

        return $result;
    }

    /**
     * Conta os primeiros contactos efetivamente enviados no dia atual.
     *
     * Considera em conjunto os canais WhatsApp e Email.
     * Uma conversação só é contabilizada se tiver pelo menos uma
     * mensagem de saída enviada hoje e nenhuma mensagem de saída
     * enviada em dias anteriores.
     *
     * Mensagens em rascunho, aprovadas ou falhadas não são contabilizadas.
     *
     * @return int Número de primeiros contactos enviados hoje.
     */
    protected function countFirstContactsToday(): int
    {
        $timezone = config('app.timezone');

        $startOfDay = now($timezone)->startOfDay();
        $endOfDay = now($timezone)->endOfDay();

        return Message::query()
            ->select('conversation_id')
            ->where('direction', Message::DIRECTION_OUTGOING)
            ->where('status', Message::STATUS_SENT)
            ->whereNotNull('sent_at')
            ->whereBetween('sent_at', [$startOfDay, $endOfDay])
            ->whereNotExists(function ($query) use ($startOfDay) {
                $query->selectRaw('1')
                    ->from('prospeccao_messages as previous')
                    ->whereColumn(
                        'previous.conversation_id',
                        'prospeccao_messages.conversation_id'
                    )
                    ->where('previous.direction', Message::DIRECTION_OUTGOING)
                    ->where('previous.status', Message::STATUS_SENT)
                    ->whereNotNull('previous.sent_at')
                    ->where('previous.sent_at', '<', $startOfDay);
            })
            ->distinct()
            ->count('conversation_id');
    }

    /**
     * Verifica se a conversação já teve um primeiro contacto enviado.
     *
     * Apenas considera mensagens de saída com estado sent e sent_at
     * preenchido. Mensagens preparadas ou aprovadas não contam.
     *
     * @param Conversation $conversation Conversação a verificar.
     *
     * @return bool True se já existir um primeiro contacto enviado.
     */
    protected function hasFirstOutgoingMessage(
        Conversation $conversation
    ): bool {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', Message::DIRECTION_OUTGOING)
            ->where('status', Message::STATUS_SENT)
            ->whereNotNull('sent_at')
            ->exists();
    }

    /**
     * Calcula a data e hora de início do próximo dia útil.
     *
     * Parte do dia seguinte à data atual e ignora sábados e domingos.
     * Utiliza o fuso horário configurado na aplicação.
     *
     * @param string $startTime Hora de início permitida (HH:mm).
     *
     * @return Carbon Data e hora do próximo dia útil.
     */
    protected function nextBusinessDayAt(string $startTime): Carbon
    {
        $timezone = config('app.timezone');

        $next = now($timezone)->startOfDay()->addDay();

        while ($next->isWeekend()) {
            $next->addDay();
        }

        return Carbon::parse(
            $next->format('Y-m-d') . ' ' . $startTime,
            $timezone
        );
    }

    /**
     * Determina quando poderá ser enviada a próxima mensagem de saída.
     *
     * Calcula o intervalo mínimo a partir da última mensagem efetivamente
     * enviada na conversação. Dá prioridade ao valor definido na
     * conversação, caso exista um override.
     *
     * @param Conversation $conversation Conversação a verificar.
     * @param array $channelSettings Configurações do canal.
     *
     * @return Carbon|null Data e hora em que o intervalo termina,
     *                     ou null se já tiver sido cumprido.
     */
    protected function minimumIntervalReadyAt(
        Conversation $conversation,
        array $channelSettings
    ): ?Carbon {
        $lastOutgoingMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', Message::DIRECTION_OUTGOING)
            ->where('status', Message::STATUS_SENT)
            ->whereNotNull('sent_at')
            ->orderByDesc('sent_at')
            ->first();

        if (!$lastOutgoingMessage) {
            return null;
        }

        $hours = $conversation->min_interval_hours_override
            ?? $channelSettings['min_interval_hours'];

        $readyAt = $lastOutgoingMessage->sent_at->copy()
            ->addHours($hours);

        return $readyAt->isFuture() ? $readyAt : null;
    }

    /**
     * Verifica se foi atingido o número máximo de seguimentos.
     *
     * Conta apenas mensagens de saída efetivamente enviadas depois
     * do primeiro contacto. Se existir uma resposta recebida do
     * proprietário, reinicia a contagem a partir dessa resposta.
     *
     * O limite definido na conversação prevalece sobre o limite
     * configurado globalmente para o canal.
     *
     * @param Conversation $conversation Conversação a verificar.
     * @param array $channelSettings Configurações do canal.
     *
     * @return bool True se o limite máximo tiver sido atingido.
     */
    protected function hasReachedMaximumFollowUps(
        Conversation $conversation,
        array $channelSettings
    ): bool {
        $maximum = $conversation->max_follow_ups_override
            ?? $channelSettings['max_follow_ups'];

        if ($maximum <= 0) {
            return false;
        }

        $lastIncomingMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', Message::DIRECTION_INCOMING)
            ->where('status', Message::STATUS_RECEIVED)
            ->whereNotNull('received_at')
            ->orderByDesc('received_at')
            ->first();

        $firstOutgoingMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', Message::DIRECTION_OUTGOING)
            ->where('status', Message::STATUS_SENT)
            ->whereNotNull('sent_at')
            ->orderBy('sent_at')
            ->first();

        if (!$firstOutgoingMessage) {
            return false;
        }

        $query = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', Message::DIRECTION_OUTGOING)
            ->where('status', Message::STATUS_SENT)
            ->whereNotNull('sent_at')
            ->where('id', '!=', $firstOutgoingMessage->id);

        if ($lastIncomingMessage) {
            $query->where('sent_at', '>', $lastIncomingMessage->received_at);
        }

        return $query->count() >= $maximum;
    }

    /**
     * Determina quando poderá ser realizado o próximo seguimento.
     *
     * O intervalo é contado a partir da última resposta recebida,
     * caso exista. Caso contrário, é contado a partir da última
     * mensagem de saída efetivamente enviada.
     *
     * O intervalo definido na conversação prevalece sobre o
     * configurado para o canal.
     *
     * @param Conversation $conversation Conversação a verificar.
     * @param array $channelSettings Configurações do canal.
     *
     * @return Carbon|null Data e hora em que o intervalo termina,
     *                     ou null se já tiver sido cumprido.
     */
    protected function followUpIntervalReadyAt(
        Conversation $conversation,
        array $channelSettings
    ): ?Carbon {
        $firstOutgoingMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', Message::DIRECTION_OUTGOING)
            ->where('status', Message::STATUS_SENT)
            ->whereNotNull('sent_at')
            ->orderBy('sent_at')
            ->first();

        if (!$firstOutgoingMessage) {
            return null;
        }

        $lastIncomingMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', Message::DIRECTION_INCOMING)
            ->where('status', Message::STATUS_RECEIVED)
            ->whereNotNull('received_at')
            ->where('received_at', '>', $firstOutgoingMessage->sent_at)
            ->orderByDesc('received_at')
            ->first();

        $lastOutgoingMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', Message::DIRECTION_OUTGOING)
            ->where('status', Message::STATUS_SENT)
            ->whereNotNull('sent_at')
            ->when(
                $lastIncomingMessage,
                fn($query) => $query->where(
                    'sent_at',
                    '>',
                    $lastIncomingMessage->received_at
                )
            )
            ->orderByDesc('sent_at')
            ->first();

        $referenceAt = $lastIncomingMessage?->received_at
            ?? $lastOutgoingMessage?->sent_at;

        if (!$referenceAt) {
            return null;
        }

        $days = $conversation->follow_up_interval_days_override
            ?? $channelSettings['follow_up_interval_days'];

        $readyAt = $referenceAt->copy()->addDays($days);

        return $readyAt->isFuture() ? $readyAt : null;
    }

    /**
     * Ajusta uma data de execução ao próximo período permitido.
     *
     * Garante que a data calculada respeita os dias úteis e o horário
     * permitido para a conversação. Se a data coincidir com um fim
     * de semana ou ultrapassar o horário permitido, avança para
     * o próximo período válido.
     *
     * Os horários definidos na conversação prevalecem sobre os
     * horários configurados para o canal.
     *
     * @param Carbon $date Data e hora inicialmente calculadas.
     * @param Conversation $conversation Conversação a verificar.
     * @param array $channelSettings Configurações do canal.
     *
     * @return Carbon Data e hora ajustadas ao período permitido.
     */
    protected function normalizeToAllowedPeriod(
        Carbon $date,
        Conversation $conversation,
        array $channelSettings
    ): Carbon {
        $timezone = config('app.timezone');

        $date = $date->copy()->setTimezone($timezone);

        $startTime = $conversation->start_time_override
            ?? $channelSettings['start_time'];

        $endTime = $conversation->end_time_override
            ?? $channelSettings['end_time'];

        while (true) {
            if ($date->isWeekend()) {
                $date->addDay()->startOfDay();

                continue;
            }

            $startAt = Carbon::parse(
                $date->format('Y-m-d') . ' ' . $startTime,
                $timezone
            );

            $endAt = Carbon::parse(
                $date->format('Y-m-d') . ' ' . $endTime,
                $timezone
            );

            if ($date->lt($startAt)) {
                return $startAt;
            }

            if ($date->gte($endAt)) {
                $date->addDay()->startOfDay();

                continue;
            }

            return $date;
        }
    }

    /**
     * Verifica se a hora atual está dentro do horário permitido.
     *
     * Se a comunicação estiver fora do horário ou num fim de semana,
     * calcula a próxima hora de início permitida.
     *
     * Os horários definidos na conversação prevalecem sobre os
     * horários configurados para o canal.
     *
     * @param Conversation $conversation Conversação a verificar.
     * @param array $channelSettings Configurações do canal.
     *
     * @return Carbon|null Próxima hora permitida, ou null se
     *                     a comunicação puder ocorrer agora.
     */
    protected function allowedHoursReadyAt(
        Conversation $conversation,
        array $channelSettings
    ): ?Carbon {
        $timezone = config('app.timezone');
        $now = now($timezone);

        $startTime = $conversation->start_time_override
            ?? $channelSettings['start_time'];

        $endTime = $conversation->end_time_override
            ?? $channelSettings['end_time'];

        if ($now->isWeekend()) {
            $next = $now->copy()->startOfDay()->addDay();

            while ($next->isWeekend()) {
                $next->addDay();
            }

            return Carbon::parse(
                $next->format('Y-m-d') . ' ' . $startTime,
                $timezone
            );
        }

        $startAt = Carbon::parse(
            $now->format('Y-m-d') . ' ' . $startTime,
            $timezone
        );

        $endAt = Carbon::parse(
            $now->format('Y-m-d') . ' ' . $endTime,
            $timezone
        );

        if ($now->lt($startAt)) {
            return $startAt;
        }

        if ($now->gte($endAt)) {
            $next = $now->copy()->addDay()->startOfDay();

            while ($next->isWeekend()) {
                $next->addDay();
            }

            return Carbon::parse(
                $next->format('Y-m-d') . ' ' . $startTime,
                $timezone
            );
        }

        return null;
    }
}
