<?php

declare(strict_types=1);

namespace App\Enums;

enum CallResult: string
{
    case NoAnswer = 'no_answer';
    case CallbackLater = 'callback_later';
    case Success = 'success';
}
