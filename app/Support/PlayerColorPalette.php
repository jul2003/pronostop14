<?php

namespace App\Support;

class PlayerColorPalette
{
    public static function colors(): array
    {
        $values = [0, 127, 255];
        $colors = [];

        foreach ($values as $red) {
            foreach ($values as $green) {
                foreach ($values as $blue) {
                    $colors[] = sprintf(
                        '#%02X%02X%02X',
                        $red,
                        $green,
                        $blue
                    );
                }
            }
        }

        return $colors;
    }
}
