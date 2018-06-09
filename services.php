<?php

use Dhii\Output\PlaceholderTemplate;
use Psr\Container\ContainerInterface;
use RebelCode\EddBookings\Emails\Module\BookingsEmailTagTemplate;
use RebelCode\EddBookings\Emails\Module\BookingsEmailTag;
use RebelCode\EddBookings\Emails\Module\EmailTagRegisterHandler;

return [
    /*
     * The handler that registers the EDD email tag handlers.
     *
     * @since [*next-version*]
     */
    'eddbk_email_tags_register_handler'        => function (ContainerInterface $c) {
        return new EmailTagRegisterHandler($c->get('eddbk_admin_emails/edd_email_tags'), $c);
    },

    /*
     * The "bookings" EDD email tag handler.
     *
     * @since [*next-version*]
     */
    'eddbk_email_bookings_tag_handler'         => function (ContainerInterface $c) {
        return new BookingsEmailTag(
            $c->get('eddbk_email_bookings_tag_template'),
            $c->get('bookings_select_rm'),
            $c->get('sql_expression_builder')
        );
    },

    /*
     * The template for the "bookings" EDD email tag.
     *
     * @since [*next-version*]
     */
    'eddbk_email_bookings_tag_template'        => function (ContainerInterface $c) {
        return new BookingsEmailTagTemplate(
            $c->get('eddbk_email_bookings_tag_layout_template'),
            $c->get('eddbk_services_select_rm'),
            $c->get('sql_expression_builder'),
            $c->get('eddbk_admin_emails/templates/bookings_table/booking_datetime_format')
        );
    },

    /**
     * The template for the bookings table layout.
     *
     * @since [*next-version*]
     */
    'eddbk_email_bookings_tag_layout_template' => function (ContainerInterface $c) {
        $template = file_get_contents(RCMOD_EDDBK_ADMIN_EMAILS_TEMPLATES_DIR . '/email-bookings-table-layout.html');
        $config   = $c->get('eddbk_admin_emails/templates/bookings_table_layout');

        return new PlaceholderTemplate(
            $template,
            $config->get('token_start'),
            $config->get('token_end'),
            $config->get('token_default')
        );
    },
];
