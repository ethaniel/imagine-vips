<?php

/*
 * This file is part of the imagine-vips package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Imagine\Vips;

use Imagine\Effects\EffectsInterface;
use Imagine\Exception\RuntimeException;
use Imagine\Image\Palette\Color\ColorInterface;
use Jcupitt\Vips\Exception;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;

/**
 * Effects implementation using the Vips PHP extension.
 */
class Effects implements EffectsInterface
{
    /**
     * @var Image
     */
    private $image;

    public function __construct(Image $image)
    {
        $this->image = $image;
    }

    /**
     * {@inheritdoc}
     */
    public function gamma($correction)
    {
        try {
            $this->image->applyToLayers(function (VipsImage $vips) use ($correction): VipsImage {
                return $vips->gamma(['exponent' => $correction]);
            });
        } catch (Exception $e) {
            throw new RuntimeException('Failed to apply gamma correction to the image', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function negative()
    {
        try {
            $this->image->applyToLayers(function (VipsImage $vips): VipsImage {
                if ($vips->hasAlpha()) {
                    $imageWithoutAlpha = $vips->extract_band(0, ['n' => $vips->bands - 1]);
                    $alpha = $vips->extract_band($vips->bands - 1, ['n' => 1]);
                    $newVips = $imageWithoutAlpha->invert()->bandjoin($alpha);
                } else {
                    $newVips = $vips->invert();
                }

                return $newVips;
            });
        } catch (Exception $e) {
            throw new RuntimeException('Failed to negate the image', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function grayscale()
    {
        try {
            $this->image->applyToLayers(function (VipsImage $vips): VipsImage {
                //FIXME: maybe more interpretations don't work
                if (Interpretation::CMYK == $vips->interpretation) {
                    $vips = $vips->icc_import(['embedded' => true]);
                }

                return $vips->colourspace(Interpretation::B_W);
            });
        } catch (Exception $e) {
            throw new RuntimeException('Failed to grayscale the image', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function colorize(ColorInterface $color)
    {
        throw new \RuntimeException(__METHOD__.' not implemented yet in the vips adapter.');
    }

    /**
     * {@inheritdoc}
     */
    public function sharpen()
    {
        try {
            $this->image->applyToLayers(function (VipsImage $vips): VipsImage {
                $oldinterpretation = $vips->interpretation;
                $vips = $vips->sharpen();
                if ($oldinterpretation != $vips->interpretation) {
                    $vips = $vips->colourspace($oldinterpretation);
                }

                return $vips;
            });
        } catch (Exception $e) {
            throw new RuntimeException('Failed to sharpen the image', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function blur($sigma = 1)
    {
        try {
            $this->image->applyToLayers(function (VipsImage $vips) use ($sigma): VipsImage {
                return $vips->gaussblur($sigma);
            });
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to blur the image', $e->getCode(), $e);
        }

        return $this;
    }
}
