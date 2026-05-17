<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function ticket_notice_config()
{
    return [
        'name' => 'Ticket Notice',
        'description' => 'Show department-based reminders before ticket submission.',
        'version' => '1.0.0',
        'author' => 'Custom',
        'language' => 'english',
    ];
}

function ticket_notice_activate()
{
    return ['status' => 'success', 'description' => 'Ticket Notice activated'];
}

function ticket_notice_deactivate()
{
    return ['status' => 'success', 'description' => 'Ticket Notice deactivated'];
}

function ticket_notice_output($vars)
{
    echo '<p>Ticket Notice MVP is active. Configure rules in hooks.php.</p>';
}
