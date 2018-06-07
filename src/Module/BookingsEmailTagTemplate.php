<?php

namespace RebelCode\EddBookings\Emails\Module;

use ArrayAccess;
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
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeIterableCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RebelCode\Bookings\BookingInterface;
use Dhii\Util\String\StringableInterface as Stringable;

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
     * The placeholder template.
     *
     * @since [*next-version*]
     *
     * @var TemplateInterface
     */
    protected $placeholderTemplate;

    /**
     * The services SELECT resource model.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $servicesSelectRm;

    /**
     * The expression builder.
     *
     * @since [*next-version*]
     *
     * @var object
     */
    protected $exprBuilder;

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
     * @param TemplateInterface      $layoutTemplate        The layout template.
     * @param SelectCapableInterface $servicesSelectRm      The services SELECT resource model.
     * @param object                 $exprBuilder           The expression builder.
     * @param Stringable|string      $bookingDateTimeFormat The booking date and time format.
     */
    public function __construct(
        TemplateInterface $layoutTemplate,
        SelectCapableInterface $servicesSelectRm,
        $exprBuilder,
        $bookingDateTimeFormat
    ) {
        $this->_setTemplate($layoutTemplate);

        $this->servicesSelectRm      = $servicesSelectRm;
        $this->exprBuilder           = $exprBuilder;
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

        $columns = sprintf('<th>%1$s</th><th>%2$s</th><th>%3$s</th>',
            $this->__('Service'),
            $this->__('Start'),
            $this->__('End')
        );

        $bookingRows = '';
        foreach ($bookings as $_booking) {
            $bookingRows .= $this->_renderBookingRow($_booking);
        }

        return $this->placeholderTemplate->render([
            'columns'      => $columns,
            'booking_rows' => $bookingRows,
        ]);
    }

    /**
     * Renders a single booking info row.
     *
     * @since [*next-version*]
     *
     * @param BookingInterface|ContainerInterface|ArrayAccess $booking The booking. Must be a booking instance and also
     *                                                                 a valid readable container.
     *
     * @return string|Stringable The rendered row.
     */
    protected function _renderBookingRow($booking)
    {
        if (!($booking instanceof BookingInterface)) {
            throw $this->_createInvalidArgumentException(
                $this->__('Argument is not a booking instance'), null, null, $booking
            );
        }

        /* @var $booking BookingInterface|ContainerInterface|ArrayAccess */
        $booking = $this->_normalizeContainer($booking);

        // Alias expression builder
        $b = $this->exprBuilder;

        // Get services that match the ID
        $serviceId = $this->_containerGet($booking, 'service_id');
        $services  = $this->servicesSelectRm->select($b->and(
            $b->eq(
                $b->ef('service', 'id'), $b->lit($serviceId)
            )
        ));
        // Fail if no services or more than 1 service matched
        if (($c = $this->_countIterable($services)) !== 1) {
            throw $this->_createRuntimeException(
                $this->__('Booking\'s service ID "%1$s" matched %2$d services', [$serviceId, $c])
            );
        }
        // Get the service
        $service = reset($services);

        try {
            // Get the service name
            $serviceName = $this->_containerGet($service, 'name');
        } catch (NotFoundExceptionInterface $exception) {
            throw $this->_createRuntimeException(
                $this->__('Failed to retrieve the service name'), null, null
            );
        }

        return sprintf(
            '<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td></tr>',
            $serviceName,
            date($this->bookingDateTimeFormat, $booking->getStart()),
            date($this->bookingDateTimeFormat, $booking->getEnd())
        );
    }
}
