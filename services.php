<?php

return [
    /*
     * The handler that registers the EDD email tag handlers.
     *
     * @since [*next-version*]
     */
    'eddbk_email_tags_register_handler'        => function (ContainerInterface $c) {
        return new EmailTagRegisterHandler($c->get('eddbk_admin_emails/email_tags'), $c);
    },

];
