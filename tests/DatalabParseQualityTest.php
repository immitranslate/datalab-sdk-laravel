<?php

use ImmiTranslate\Datalab\Enums\DatalabParseQuality;

it('maps scores to quality bands at the documented boundaries', function (float $score, DatalabParseQuality $expected) {
    expect(DatalabParseQuality::fromScore($score))->toBe($expected);
})->with([
    [5.0, DatalabParseQuality::Excellent],
    [4.0, DatalabParseQuality::Excellent],
    [3.9, DatalabParseQuality::Good],
    [3.0, DatalabParseQuality::Good],
    [2.9, DatalabParseQuality::Fair],
    [2.0, DatalabParseQuality::Fair],
    [1.9, DatalabParseQuality::Poor],
    [0.0, DatalabParseQuality::Poor],
]);

it('exposes a recommended action for every band', function () {
    foreach (DatalabParseQuality::cases() as $band) {
        expect($band->recommendedAction())->toBeString()->not->toBe('');
    }
});
