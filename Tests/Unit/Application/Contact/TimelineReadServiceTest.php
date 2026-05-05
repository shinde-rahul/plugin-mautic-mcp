<?php

declare(strict_types=1);

namespace MauticPlugin\MauticMcpBundle\Tests\Unit\Application\Contact;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticMcpBundle\Application\Contact\ContactReadService;
use MauticPlugin\MauticMcpBundle\Application\Contact\TimelineReadService;
use MauticPlugin\MauticMcpBundle\Domain\Timeline\TimelineProviderInterface;
use MauticPlugin\MauticMcpBundle\Presentation\Normalizer\TimelineEventNormalizer;
use MauticPlugin\MauticMcpBundle\Presentation\Normalizer\TimelinePayloadNormalizer;
use PHPUnit\Framework\TestCase;

final class TimelineReadServiceTest extends TestCase
{
    public function testGetTimelineLoadsAViewableContactBeforeReadingEvents(): void
    {
        $contact = new Lead();
        $contact->setId(11);

        $contactReadService = $this->createMock(ContactReadService::class);
        $contactReadService->expects($this->once())
            ->method('getViewableContactOrFail')
            ->with(11)
            ->willReturn($contact);

        $timelineProvider = $this->createMock(TimelineProviderInterface::class);
        $timelineProvider->expects($this->once())
            ->method('getTimeline')
            ->with(
                $contact,
                [
                    'search'        => 'email',
                    'includeEvents' => [],
                    'excludeEvents' => [],
                ],
                ['timestamp', 'DESC'],
                2,
                5,
            )
            ->willReturn([
                'page'     => 2,
                'limit'    => 5,
                'total'    => 1,
                'maxPages' => 3,
                'types'    => ['email.sent' => 'Email sent'],
                'events'   => [
                    [
                        'event'      => 'email.sent',
                        'eventType'  => 'Email sent',
                        'eventLabel' => 'Email sent',
                        'contactId'  => 11,
                    ],
                ],
            ]);

        $service = new TimelineReadService(
            $contactReadService,
            $timelineProvider,
            new TimelinePayloadNormalizer(new TimelineEventNormalizer()),
        );

        $this->assertSame(
            [
                'contact_id' => 11,
                'page'       => 2,
                'limit'      => 5,
                'total'      => 1,
                'max_pages'  => 3,
                'types'      => ['email.sent'],
                'events'     => [
                    [
                        'id'          => null,
                        'event'       => 'email.sent',
                        'type'        => 'Email sent',
                        'label'       => 'Email sent',
                        'occurred_at' => null,
                        'contact_id'  => 11,
                        'icon'        => null,
                        'details'     => [],
                    ],
                ],
            ],
            $service->getTimeline(11, 'email', 5, 2),
        );
    }
}
