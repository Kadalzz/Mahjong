<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;

class TokensController extends Controller
{
    public function __invoke()
    {
        abort_unless(app()->isLocal(), 404);

        return view('dev.tokens', [
            'colors' => [
                'Brand'   => ['brand-green', 'brand-green-deep', 'brand-red', 'brand-red-light'],
                'Accent'  => ['accent-gold', 'accent-gold-light'],
                'Button'  => ['button-primary', 'button-primary-hover', 'button-primary-pressed', 'button-primary-border', 'button-text'],
                'Neutral' => ['neutral-cream', 'neutral-cream-dark', 'neutral-white'],
                'Text'    => ['text-ink', 'text-mute'],
                'Status'  => ['status-success', 'status-success-bg'],
                'Alpha'   => [
                    'alpha-cream-4', 'alpha-cream-7', 'alpha-cream-10', 'alpha-cream-14', 'alpha-cream-45', 'alpha-cream-50', 'alpha-cream-55', 'alpha-cream-65', 'alpha-cream-70',
                    'alpha-gold-0', 'alpha-gold-25', 'alpha-gold-40', 'alpha-gold-45', 'alpha-gold-50', 'alpha-gold-55', 'alpha-gold-60', 'alpha-gold-70',
                    'alpha-gold-light-40', 'alpha-gold-light-60', 'alpha-ink-14', 'alpha-mute-0', 'alpha-mute-35', 'alpha-red-light-0',
                ],
            ],
            'types' => [
                't-display-hero' => 'Display/Hero · Baloo 2 ExtraBold 64',
                't-h1'           => 'Heading/H1 · Baloo 2 ExtraBold 36',
                't-h2'           => 'Heading/H2 · Baloo 2 ExtraBold 30',
                't-h3'           => 'Heading/H3 · Baloo 2 ExtraBold 24',
                't-h4'           => 'Heading/H4 · Baloo 2 ExtraBold 20',
                't-h5'           => 'Heading/H5 · Baloo 2 ExtraBold 18',
                't-title-lg'     => 'Title/Large · Baloo 2 Bold 24',
                't-title-md'     => 'Title/Medium · Baloo 2 Bold 20',
                't-title-sm'     => 'Title/Small · Baloo 2 Bold 16',
                't-button'       => 'Button/Label · Baloo 2 Bold 18 · 3%',
                't-label-strong' => 'Label/Strong · Baloo 2 ExtraBold 15',
                't-body-lg'      => 'Body/Large · Nunito Regular 15',
                't-body'         => 'Body/Regular · Nunito Regular 13',
                't-body-sm'      => 'Body/Small · Nunito Regular 11',
                't-body-bold'    => 'Body/Bold · Nunito Bold 13',
                't-caption-bold' => 'Caption/Bold · Nunito Bold 9',
                't-tab'          => 'Label/Tab · Nunito ExtraBold 13',
                't-kicker'       => 'Label/Kicker · Nunito ExtraBold 10 · 24%',
                't-pill'         => 'Label/Pill · Nunito ExtraBold 10 · 10%',
                't-caption'      => 'Caption/Strong · Nunito ExtraBold 10',
            ],
            'spaces'  => ['0-5', '1', '1-5', '2', '2-5', '3', '3-5', '4', '4-5', '6', '7', '8', '9', '10', '12', '16'],
            'radii'   => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', 'full'],
            'strokes' => ['hairline', 'thin', 'base', 'emphasis', 'bold'],
            'shadows' => [
                'elevation-low', 'elevation-medium', 'elevation-high', 'elevation-highest',
                'glow-primary', 'glow-primary-hover', 'glow-win-hover', 'glow-red', 'glow-success', 'glow-scan',
                'inset-pressed', 'inset-soft', 'depth-primary', 'depth-primary-hover', 'depth-primary-pressed', 'depth-locked',
            ],
            'icons' => [
                'chevron-left', 'chevron-right', 'arrow-down', 'plus', 'check', 'diamond', 'info', 'rotate-cw', 'lock', 'user',
                'trophy', 'camera', 'pencil', 'log-out', 'image', 'trash', 'wifi-off', 'alert', 'inbox', 'camera-off',
            ],
            'illustrations' => ['trophy', 'win-badge', 'broken-tile', 'lose-badge', 'broken-trophy', 'trophy-lose'],
        ]);
    }
}
