<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Extractor;

use App\Extractor\BambooSlackMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class BambooSlackMessageTest extends TestCase
{
    /**
     * @test
     */
    public function constructorExtractsValues()
    {
        $payload = new Request(
            [],
            [
                'payload' => '"attachments":[{"color":"good","text":"<https://bamboo.typo3.com/browse/T3G-AP-25|T3G \u203a Apparel \u203a #25> passed. 6 passed. Manual run by <https://bamboo.typo3.com/browse/user/susanne.moog|Susanne Moog>","fallback":"T3G \u203a Apparel \u203a #25 passed. 6 passed. Manual run by Susanne Moog"}],"username":"Bamboo"}'
            ]
        );
        $subject = new BambooSlackMessage($payload);
        $this->assertSame('T3G-AP-25', $subject->buildKey);
        $this->assertFalse($subject->isNightlyBuild);
    }

    /**
     * @test
     */
    public function constructorSetsNightlyBuildTrue()
    {
        $payload = new Request(
            [],
            [
                'payload' => '"attachments":[{"color":"good","text":"<https://bamboo.typo3.com/browse/CORE-GTN-1234|"}'
            ]
        );
        $subject = new BambooSlackMessage($payload);
        $this->assertTrue($subject->isNightlyBuild);
    }

    /**
     * @test
     */
    public function constructorThrowsIfBuildKeyWasNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        new BambooSlackMessage(new Request([], []));
    }
}
