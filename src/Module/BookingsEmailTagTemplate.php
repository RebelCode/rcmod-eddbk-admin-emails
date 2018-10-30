<?php

namespace RebelCode\EddBookings\Emails\Module;

use ArrayAccess;
use DateTime;
use DateTimeZone;
use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\NormalizeContainerCapableTrait;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Exception\CreateRuntimeExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Iterator\CountIterableCapableTrait;
use Dhii\Iterator\ResolveIteratorCapableTrait;
use Dhii\Output\CreateRendererExceptionCapableTrait;
use Dhii\Output\CreateTemplateRenderExceptionCapableTrait;
use Dhii\Output\TemplateAwareTrait;
use Dhii\Output\TemplateInterface;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeIterableCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RebelCode\Entity\GetCapableManagerInterface;
use stdClass;

/**
 * The template for the bookings email tag.
 *
 * This implementation uses another template for the table layout, passing it the pre-rendered columns and rows.
 *
 * @since [*next-version*]
 */
class BookingsEmailTagTemplate implements TemplateInterface
{
    /* @since [*next-version*] */
    use TemplateAwareTrait;

    /* @since [*next-version*] */
    use ContainerGetCapableTrait;

    /* @since [*next-version*] */
    use CountIterableCapableTrait;

    /* @since [*next-version*] */
    use ResolveIteratorCapableTrait;

    /* @since [*next-version*] */
    use NormalizeIntCapableTrait;

    /* @since [*next-version*] */
    use NormalizeKeyCapableTrait;

    /* @since [*next-version*] */
    use NormalizeStringCapableTrait;

    /* @since [*next-version*] */
    use NormalizeIterableCapableTrait;

    /* @since [*next-version*] */
    use NormalizeContainerCapableTrait;

    /* @since [*next-version*] */
    use CreateContainerExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateNotFoundExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateTemplateRenderExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateRendererExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateRuntimeExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The services manger for retrieving services by ID.
     *
     * @since [*next-version*]
     *
     * @var GetCapableManagerInterface
     */
    protected $servicesManager;

    /**
     * The format to use to render booking dates and times.
     *
     * @since [*next-version*]
     *
     * @var string|Stringable
     */
    protected $bookingDateTimeFormat;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param TemplateInterface          $layoutTemplate        The layout template.
     * @param GetCapableManagerInterface $servicesManager       The services manager for retrieving services by ID.
     * @param Stringable|string          $bookingDateTimeFormat The booking date and time format.
     */
    public function __construct(
        TemplateInterface $layoutTemplate,
        GetCapableManagerInterface $servicesManager,
        $bookingDateTimeFormat
    ) {
        $this->_setTemplate($layoutTemplate);

        $this->servicesManager       = $servicesManager;
        $this->bookingDateTimeFormat = $bookingDateTimeFormat;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function render($context = null)
    {
        try {
            $bookings = $this->_containerGet($context, 'bookings');
        } catch (NotFoundExceptionInterface $exception) {
            throw $this->_createTemplateRenderException(
                $this->__('Bookings not found in render context'), null, null, $this, $context
            );
        }

        try {
            $this->_normalizeIterable($bookings);
        } catch (InvalidArgumentException $exception) {
            throw $this->_createTemplateRenderException(
                $this->__('Invalid bookings in render context - not an iterable'), null, null, $this, $context
            );
        }

        $columns = sprintf('<th>%1$s</th><th>%2$s</th>',
            $this->__('Service'),
            $this->__('When')
        );

        $bookingRows = '';
        foreach ($bookings as $_booking) {
            $bookingRows .= $this->_renderBookingRow($_booking);
        }

        return $this->_getTemplate()->render([
            'columns'      => $columns,
            'booking_rows' => $bookingRows,
        ]);
    }

    /**
     * Renders a single booking info row.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|ArrayAccess|ContainerInterface $booking The booking data container.
     *
     * @return string|Stringable The rendered row.
     */
    protected function _renderBookingRow($booking)
    {
        $booking   = $this->_normalizeContainer($booking);
        $serviceId = $this->_containerGet($booking, 'service_id');

        try {
            // Get the service
            $service = $this->servicesManager->get($serviceId);
        } catch (NotFoundExceptionInterface $exception) {
            throw $this->_createRuntimeException(
                $this->__('Failed to retrieve the service for the booking'), null, null
            );
        }

        $serviceName = $this->_containerGet($service, 'name');

        $start = $this->_containerGet($booking, 'start');
        $start = new DateTime('@' . $start, new DateTimeZone('UTC'));

        try {
            $clientTz = $this->_containerGet($booking, 'client_tz');
            if (!empty($clientTz)) {
                $start->setTimezone(new DateTimeZone($clientTz));
            }
        } catch (NotFoundExceptionInterface $nfException) {
            // booking has no client timezone
        } catch (Exception $exception) {
            // failed to parse timezone name
        }

        return sprintf(
            '<tr><td>%1$s</td><td>%2$s</td></tr>',
            $serviceName,
            $start->format($this->bookingDateTimeFormat)
        );
    }
}
