<?php

namespace Netgen\TagsBundle\Tests\Core\Persistence\Legacy\Content;

use eZ\Publish\SPI\Persistence\Content\Language;
use eZ\Publish\SPI\Persistence\Content\Language\Handler as LanguageHandler;
use PHPUnit\Framework\TestCase;

/**
 * Simple mock provider for a Language\Handler.
 */
class LanguageHandlerMock
{
    protected $languages = array();

    public function __construct()
    {
        $this->languages['eng-US'] = new Language(
            array(
                'id' => 2,
                'languageCode' => 'eng-US',
                'name' => 'English (American)',
            )
        );
        $this->languages['ger-DE'] = new Language(
            array(
                'id' => 4,
                'languageCode' => 'ger-DE',
                'name' => 'German',
            )
        );
        $this->languages['eng-GB'] = new Language(
            array(
                'id' => 8,
                'languageCode' => 'eng-GB',
                'name' => 'English (United Kingdom)',
            )
        );
    }

    public function __invoke(TestCase $testCase)
    {
        $mock = $testCase->getMockBuilder(LanguageHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($testCase::any())
            ->method('load')
            ->will(
                $testCase::returnValueMap(
                    array(
                        array(2, $this->languages['eng-US']),
                        array(4, $this->languages['ger-DE']),
                        array(8, $this->languages['eng-GB']),
                        array('2', $this->languages['eng-US']),
                        array('4', $this->languages['ger-DE']),
                        array('8', $this->languages['eng-GB']),
                    )
                )
            );

        $mock->expects($testCase::any())
            ->method('loadByLanguageCode')
            ->will(
                $testCase::returnValueMap(
                    array(
                        array('eng-US', $this->languages['eng-US']),
                        array('ger-DE', $this->languages['ger-DE']),
                        array('eng-GB', $this->languages['eng-GB']),
                    )
                )
            );

        $mock->expects($testCase::any())
            ->method('loadListByLanguageCodes')
            ->will(
                $testCase::returnCallback(
                    function (array $languageCodes) {
                        return iterator_to_array(
                            (function () use ($languageCodes) {
                                foreach ($languageCodes as $languageCode) {
                                    yield $languageCode => $this->languages[$languageCode];
                                }
                            })()
                        );
                    }
                )
            );

        $mock->expects($testCase::any())
            ->method('loadAll')
            ->will($testCase::returnValue(array_values($this->languages)));

        return $mock;
    }
}
