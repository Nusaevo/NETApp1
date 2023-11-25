<?php

return [
    'success' => [
        'title' => 'Success',
        'create' => 'Successfully created data :object',
        'update' => 'Successfully updated data :object',
        'delete' => 'Successfully deleted data :object',
        'disable' => 'Successfully disabled data :object',
        'enable' => 'Successfully enabled data :object',
    ],
    'error' => [
        'title' => 'Failed',
        'create' => "Failed to create data :object,\nError message: :message",
        'update' => "Failed to update data :object,\nError message: :message",
        'delete' => "Failed to delete data :object,\nError message: :message",
        'disable' => "Failed to disable data :object,\nError message: :message",
        'enable' => "Failed to enable data :object,\nError message: :message",
        'password_mismatch' => "Password mismatch. Please make sure your new password matches the confirmation password.",
        'password_must_be_filled' => "Password must be filled.",
    ],
];
