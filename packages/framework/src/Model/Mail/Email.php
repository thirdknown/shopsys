<?php

declare(strict_types=1);

namespace Shopsys\FrameworkBundle\Model\Mail;

use Symfony\Component\Mime\Email as BaseEmail;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;

class Email extends BaseEmail
{
    /**
     * @param int $domainId
     * @param \Symfony\Component\Mime\Header\Headers|null $headers
     * @param \Symfony\Component\Mime\Part\AbstractPart|null $body
     */
    public function __construct(
        protected readonly int $domainId,
        ?Headers $headers = null,
        ?AbstractPart $body = null,
    ) {
        parent::__construct($headers, $body);
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }
}
