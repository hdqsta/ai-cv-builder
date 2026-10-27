<?php

namespace App\Enums;

enum CvStatus: string
{
    case Pending = 'pending';
    case Tailoring = 'tailoring';
    case Review = 'review';
    case Scoring = 'scoring';
    case Completed = 'completed';
    case Failed = 'failed';
}
