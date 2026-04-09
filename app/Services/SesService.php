<?php

namespace App\Services;

use Aws\Ses\SesClient;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;

class SesService
{
    protected SesClient $sesClient;

    public function __construct(SesClient $sesClient)
    {
        $this->sesClient = $sesClient;
    }

    /**
     * Send a simple email
     */
    public function sendEmail(array $data): array
    {
        try {
            $limit = (int) (config('aws.ses.rate_limit_per_second') ?? env('SES_RATE_LIMIT_PER_SECOND', 14));
            $blockSeconds = 5; // esperar a disponibilidad de slot

            $response = null;
            if ($this->canUseRedisThrottle()) {
                Redis::throttle('ses-send')
                    ->allow(max(1, $limit))
                    ->every(1)
                    ->block($blockSeconds)
                    ->then(function () use ($data, &$response) {
                        $result = $this->sesClient->sendEmail([
                            'Source' => $data['from'] ?? config('mail.from.address'),
                            'Destination' => [
                                'ToAddresses' => is_array($data['to']) ? $data['to'] : [$data['to']],
                                'CcAddresses' => $data['cc'] ?? [],
                                'BccAddresses' => $data['bcc'] ?? [],
                            ],
                            'Message' => [
                                'Subject' => [
                                    'Data' => $data['subject'],
                                    'Charset' => 'UTF-8',
                                ],
                                'Body' => [
                                    'Text' => [
                                        'Data' => $data['text'] ?? '',
                                        'Charset' => 'UTF-8',
                                    ],
                                    'Html' => [
                                        'Data' => $data['html'] ?? '',
                                        'Charset' => 'UTF-8',
                                    ],
                                ],
                            ],
                            'ReplyToAddresses' => $data['reply_to'] ?? [],
                        ]);

                        $response = [
                            'success' => true,
                            'message_id' => $result['MessageId'],
                        ];
                    }, function () use (&$response) {
                        $response = [
                            'success' => false,
                            'error' => 'SES rate limit exceeded, try again shortly',
                        ];
                    });
            } else {
                // Fallback simple basado en RateLimiter (TTL 1s). Requiere cache compartido.
                $key = 'ses-send:' . time();
                if (RateLimiter::tooManyAttempts($key, max(1, $limit))) {
                    return [
                        'success' => false,
                        'error' => 'SES rate limit exceeded, try again shortly',
                    ];
                }
                RateLimiter::hit($key, 1);

                $result = $this->sesClient->sendEmail([
                    'Source' => $data['from'] ?? config('mail.from.address'),
                    'Destination' => [
                        'ToAddresses' => is_array($data['to']) ? $data['to'] : [$data['to']],
                        'CcAddresses' => $data['cc'] ?? [],
                        'BccAddresses' => $data['bcc'] ?? [],
                    ],
                    'Message' => [
                        'Subject' => [
                            'Data' => $data['subject'],
                            'Charset' => 'UTF-8',
                        ],
                        'Body' => [
                            'Text' => [
                                'Data' => $data['text'] ?? '',
                                'Charset' => 'UTF-8',
                            ],
                            'Html' => [
                                'Data' => $data['html'] ?? '',
                                'Charset' => 'UTF-8',
                            ],
                        ],
                    ],
                    'ReplyToAddresses' => $data['reply_to'] ?? [],
                ]);

                return [
                    'success' => true,
                    'message_id' => $result['MessageId'],
                ];
            }

            return $response ?? [
                'success' => false,
                'error' => 'Unknown rate limiter state',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a templated email
     */
    public function sendTemplatedEmail(array $data): array
    {
        try {
            $limit = (int) (config('aws.ses.rate_limit_per_second') ?? env('SES_RATE_LIMIT_PER_SECOND', 14));
            $blockSeconds = 5;

            $response = null;
            if ($this->canUseRedisThrottle()) {
                Redis::throttle('ses-send')
                    ->allow(max(1, $limit))
                    ->every(1)
                    ->block($blockSeconds)
                    ->then(function () use ($data, &$response) {
                        $result = $this->sesClient->sendTemplatedEmail([
                            'Source' => $data['from'] ?? config('mail.from.address'),
                            'Destination' => [
                                'ToAddresses' => is_array($data['to']) ? $data['to'] : [$data['to']],
                                'CcAddresses' => $data['cc'] ?? [],
                                'BccAddresses' => $data['bcc'] ?? [],
                            ],
                            'Template' => $data['template'],
                            'TemplateData' => json_encode($data['template_data'] ?? []),
                            'ReplyToAddresses' => $data['reply_to'] ?? [],
                        ]);

                        $response = [
                            'success' => true,
                            'message_id' => $result['MessageId'],
                        ];
                    }, function () use (&$response) {
                        $response = [
                            'success' => false,
                            'error' => 'SES rate limit exceeded, try again shortly',
                        ];
                    });
            } else {
                $key = 'ses-send:' . time();
                if (RateLimiter::tooManyAttempts($key, max(1, $limit))) {
                    return [
                        'success' => false,
                        'error' => 'SES rate limit exceeded, try again shortly',
                    ];
                }
                RateLimiter::hit($key, 1);

                $result = $this->sesClient->sendTemplatedEmail([
                    'Source' => $data['from'] ?? config('mail.from.address'),
                    'Destination' => [
                        'ToAddresses' => is_array($data['to']) ? $data['to'] : [$data['to']],
                        'CcAddresses' => $data['cc'] ?? [],
                        'BccAddresses' => $data['bcc'] ?? [],
                    ],
                    'Template' => $data['template'],
                    'TemplateData' => json_encode($data['template_data'] ?? []),
                    'ReplyToAddresses' => $data['reply_to'] ?? [],
                ]);

                return [
                    'success' => true,
                    'message_id' => $result['MessageId'],
                ];
            }

            return $response ?? [
                'success' => false,
                'error' => 'Unknown rate limiter state',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify an email address
     */
    public function verifyEmailAddress(string $email): array
    {
        try {
            $this->sesClient->verifyEmailIdentity([
                'EmailAddress' => $email,
            ]);

            return [
                'success' => true,
                'message' => 'Verification email sent to ' . $email,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get sending statistics
     */
    public function getSendingStatistics(): array
    {
        try {
            $result = $this->sesClient->getSendStatistics();

            return [
                'success' => true,
                'statistics' => $result['SendDataPoints'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get verified email addresses
     */
    public function getVerifiedEmailAddresses(): array
    {
        try {
            $result = $this->sesClient->listVerifiedEmailAddresses();

            return [
                'success' => true,
                'verified_emails' => $result['VerifiedEmailAddresses'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send an email with attachment using SES SendRawEmail
     */
    public function sendEmailWithAttachment(array $data): array
    {
        try {
            $from = $data['from'] ?? config('mail.from.address');
            $to = is_array($data['to']) ? $data['to'] : [$data['to']];
            $cc = $data['cc'] ?? [];
            $bcc = $data['bcc'] ?? [];
            $subject = $data['subject'] ?? '';
            $text = $data['text'] ?? '';
            $html = $data['html'] ?? '';
            $replyTo = $data['reply_to'] ?? [];

            $attachmentName = $data['attachment_name'] ?? 'attachment.pdf';
            $attachmentBytes = $data['attachment_bytes'] ?? null;
            $contentType = $data['content_type'] ?? 'application/pdf';

            if ($attachmentBytes === null) {
                return [
                    'success' => false,
                    'error' => 'attachment_bytes is required',
                ];
            }

            $mixedBoundary = 'mixed_' . md5(uniqid('', true));
            $altBoundary = 'alt_' . md5(uniqid('', true));

            $headers = [];
            $headers[] = 'From: ' . $from;
            $headers[] = 'To: ' . implode(', ', $to);
            if (!empty($cc)) { $headers[] = 'Cc: ' . implode(', ', is_array($cc) ? $cc : [$cc]); }
            if (!empty($bcc)) { $headers[] = 'Bcc: ' . implode(', ', is_array($bcc) ? $bcc : [$bcc]); }
            if (!empty($replyTo)) { $headers[] = 'Reply-To: ' . implode(', ', is_array($replyTo) ? $replyTo : [$replyTo]); }
            $headers[] = 'Subject: ' . $subject;
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"';

            $message = [];
            $message[] = '--' . $mixedBoundary;
            $message[] = 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"';
            $message[] = '';
            $message[] = '--' . $altBoundary;
            $message[] = 'Content-Type: text/plain; charset="UTF-8"';
            $message[] = 'Content-Transfer-Encoding: 7bit';
            $message[] = '';
            $message[] = $text;
            $message[] = '';
            $message[] = '--' . $altBoundary;
            $message[] = 'Content-Type: text/html; charset="UTF-8"';
            $message[] = 'Content-Transfer-Encoding: 7bit';
            $message[] = '';
            $message[] = $html;
            $message[] = '';
            $message[] = '--' . $altBoundary . '--';
            $message[] = '';

            $message[] = '--' . $mixedBoundary;
            $message[] = 'Content-Type: ' . $contentType . '; name="' . $attachmentName . '"';
            $message[] = 'Content-Disposition: attachment; filename="' . $attachmentName . '"';
            $message[] = 'Content-Transfer-Encoding: base64';
            $message[] = '';
            $message[] = chunk_split(base64_encode($attachmentBytes), 76, "\r\n");
            $message[] = '';
            $message[] = '--' . $mixedBoundary . '--';

            $rawMessage = implode("\r\n", array_merge($headers, [''], $message));

            $limit = (int) (config('aws.ses.rate_limit_per_second') ?? env('SES_RATE_LIMIT_PER_SECOND', 14));
            $blockSeconds = 5;

            $response = null;
            if ($this->canUseRedisThrottle()) {
                Redis::throttle('ses-send')
                    ->allow(max(1, $limit))
                    ->every(1)
                    ->block($blockSeconds)
                    ->then(function () use (&$response, $rawMessage, $to, $cc, $bcc) {
                        $result = $this->sesClient->sendRawEmail([
                            'RawMessage' => ['Data' => $rawMessage],
                            'Destinations' => array_values(array_unique(array_merge($to, is_array($cc) ? $cc : [$cc], is_array($bcc) ? $bcc : [$bcc]))),
                        ]);

                        $response = [
                            'success' => true,
                            'message_id' => $result['MessageId'],
                        ];
                    }, function () use (&$response) {
                        $response = [
                            'success' => false,
                            'error' => 'SES rate limit exceeded, try again shortly',
                        ];
                    });
            } else {
                $key = 'ses-send:' . time();
                if (RateLimiter::tooManyAttempts($key, max(1, $limit))) {
                    return [
                        'success' => false,
                        'error' => 'SES rate limit exceeded, try again shortly',
                    ];
                }
                RateLimiter::hit($key, 1);

                $result = $this->sesClient->sendRawEmail([
                    'RawMessage' => ['Data' => $rawMessage],
                    'Destinations' => array_values(array_unique(array_merge($to, is_array($cc) ? $cc : [$cc], is_array($bcc) ? $bcc : [$bcc]))),
                ]);

                return [
                    'success' => true,
                    'message_id' => $result['MessageId'],
                ];
            }

            return $response ?? [
                'success' => false,
                'error' => 'Unknown rate limiter state',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function canUseRedisThrottle(): bool
    {
        try {
            Redis::connection();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}