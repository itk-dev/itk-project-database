<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Initiative;
use App\Entity\InitiativeImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

final class InitiativeImageTest extends TestCase
{
    public function testAccessors(): void
    {
        $initiative = new Initiative();
        $image = (new InitiativeImage())
            ->setInitiative($initiative)
            ->setImageName('stored.png')
            ->setOriginalName('sample.png')
            ->setMimeType('image/png')
            ->setSize(1234)
            ->setAlt('A sample');

        self::assertNull($image->getId());
        self::assertSame($initiative, $image->getInitiative());
        self::assertSame('stored.png', $image->getImageName());
        self::assertSame('sample.png', $image->getOriginalName());
        self::assertSame('image/png', $image->getMimeType());
        self::assertSame(1234, $image->getSize());
        self::assertSame('A sample', $image->getAlt());
    }

    public function testSettingAFileMarksItDirty(): void
    {
        $image = new InitiativeImage();
        self::assertNull($image->getImageFile());
        self::assertFalse($image->hasFile());

        $image->setImageFile(new File(__FILE__));
        self::assertInstanceOf(File::class, $image->getImageFile());
        self::assertTrue($image->hasFile());

        $image->setImageFile(null);
        self::assertNull($image->getImageFile());
    }

    public function testHasFileIsTrueWhenOnlyAStoredNameIsPresent(): void
    {
        $image = (new InitiativeImage())->setImageName('stored.png');

        self::assertTrue($image->hasFile());
    }
}
