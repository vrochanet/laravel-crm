<?php

namespace Webkul\FSBO\Services;

use Webkul\FSBO\Models\CommunicationTemplate;
use Webkul\FSBO\Models\ContactLog;
use Webkul\FSBO\Models\Property;
use Webkul\User\Contracts\User as UserContract;

class ContactLogService
{
    public function __construct(
        protected TemplateParserService $parser
    ) {}

    /**
     * Prepare a filled message from a template (for review before send).
     *
     * @return array{subject: string|null, body: string}
     */
    public function prepare(
        CommunicationTemplate $template,
        Property $property,
        ?UserContract $consultant = null
    ): array {
        return $this->parser->parseTemplate($template, $property, $consultant);
    }

    /**
     * Persist a static snapshot of the prepared/sent message.
     * Future template edits must not alter this history.
     */
    public function log(
        Property $property,
        string $channel,
        string $body,
        ?string $subject = null,
        ?CommunicationTemplate $template = null,
        ?UserContract $consultant = null
    ): ContactLog {
        $userId = $consultant?->id
            ?? auth()->guard('user')->id();

        return ContactLog::create([
            'property_id' => $property->id,
            'template_id' => $template?->id,
            'user_id' => $userId,
            'channel' => $channel,
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * Parse template, then store the static prepared copy in one step.
     */
    public function prepareAndLog(
        CommunicationTemplate $template,
        Property $property,
        string $channel,
        ?UserContract $consultant = null,
        ?string $bodyOverride = null,
        ?string $subjectOverride = null
    ): ContactLog {
        $prepared = $this->prepare($template, $property, $consultant);

        return $this->log(
            property: $property,
            channel: $channel,
            body: $bodyOverride ?? $prepared['body'],
            subject: $subjectOverride ?? $prepared['subject'],
            template: $template,
            consultant: $consultant
        );
    }
}
