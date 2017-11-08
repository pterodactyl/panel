<?php

if (! empty($greeting)) {
    echo $greeting, "\n\n";
} else {
    echo $level == 'error' ? @lang('email.common.whoops') . '!' : @lang('email.common.greeting') . '!', "\n\n";
}

if (! empty($introLines)) {
    echo implode("\n", $introLines), "\n\n";
}

if (isset($actionText)) {
    echo "{$actionText}: {$actionUrl}", "\n\n";
}

if (! empty($outroLines)) {
    echo implode("\n", $outroLines), "\n\n";
}

echo @lang('email.common.regards'), "\n";
echo Settings::get('company'), "\n";
