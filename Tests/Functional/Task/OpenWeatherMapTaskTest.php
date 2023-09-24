<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/weather2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Weather2\Tests\Functional\Task;

use GuzzleHttp\Psr7\Response;
use JWeiland\Weather2\Domain\Model\CurrentWeather;
use JWeiland\Weather2\Task\OpenWeatherMapTask;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Test case.
 */
class OpenWeatherMapTaskTest extends FunctionalTestCase
{
    /**
     * @var Stream
     */
    protected $stream;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $responseMock;

    /**
     * @var RequestFactory|MockObject
     */
    protected $requestFactoryMock;

    /**
     * @var PersistenceManagerInterface|MockObject
     */
    protected $persistenceManagerMock;

    /**
     * @var OpenWeatherMapTask
     */
    protected $subject;

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'scheduler',
    ];

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/weather2',
        'typo3conf/ext/static_info_tables',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $this->stream = new Stream('php://temp', 'rw');

        $this->responseMock = $this->createMock(Response::class);
        $this->responseMock
            ->expects(self::atLeastOnce())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->requestFactoryMock = $this->createMock(RequestFactory::class);
        $this->requestFactoryMock
            ->expects(self::once())
            ->method('request')
            ->with(self::isType('string'))
            ->willReturn($this->responseMock);

        GeneralUtility::addInstance(RequestFactory::class, $this->requestFactoryMock);

        $this->persistenceManagerMock = $this->createMock(PersistenceManager::class);
        $this->persistenceManagerMock
            ->expects(self::once())
            ->method('persistAll');

        /** @var ObjectManagerInterface|MockObject $objectManagerMock */
        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock
            ->expects(self::once())
            ->method('get')
            ->with(self::identicalTo(PersistenceManager::class))
            ->willReturn($this->persistenceManagerMock);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerMock);

        // We have to use GM:makeInstance because of LoggerAwareInterface
        $this->subject = GeneralUtility::makeInstance(OpenWeatherMapTask::class);
        $this->subject->city = 'Filderstadt';
        $this->subject->apiKey = 'IHaveForgottenToAddOne';
        $this->subject->clearCache = '';
        $this->subject->country = 'Germany';
        $this->subject->recordStoragePage = 1;
        $this->subject->name = 'Filderstadt';
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->persistenceManagerMock,
            $this->requestFactoryMock,
            $this->responseMock,
            $this->stream
        );

        parent::tearDown();
    }

    /**
     * @test
     * @throws \JsonException
     */
    public function execute(): void
    {
        $this->stream->write(
            json_encode([
                'cod' => true,
                'dt' => time(),
                'main' => [
                    'temp' => 14.6,
                    'pressure' => 8,
                    'humidity' => 12,
                    'temp_min' => 13.2,
                    'temp_max' => 16.4,
                ],
                'wind' => [
                    'speed' => 3.7,
                    'deg' => 25,
                ],
                'snow' => [
                    '1h' => 4.0,
                    '3h' => 11.0,
                ],
                'rain' => [
                    '1h' => 6.0,
                    '3h' => 15.0,
                ],
                'clouds' => [
                    'all' => 11,
                ],
                'weather' => [
                    0 => [
                        'id' => 1256,
                        'main' => 'rain',
                        'icon' => '[ICON]',
                    ],
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $this->responseMock
            ->expects(self::atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->persistenceManagerMock
            ->expects(self::once())
            ->method('add')
            ->with(self::callback(static function (CurrentWeather $currentWeather) {
                return $currentWeather->getName() === 'Filderstadt'
                    && $currentWeather->getMeasureTimestamp() instanceof \DateTime
                    && $currentWeather->getTemperatureC() === 14.6
                    && $currentWeather->getPressureHpa() === 8
                    && $currentWeather->getHumidityPercentage() === 12
                    && $currentWeather->getMinTempC() === 13.2
                    && $currentWeather->getMaxTempC() === 16.4
                    && $currentWeather->getWindSpeedMPS() === 3.7
                    && $currentWeather->getWindDirectionDeg() === 25
                    && $currentWeather->getSnowVolume() === 4.0
                    && $currentWeather->getRainVolume() === 6.0
                    && $currentWeather->getCloudsPercentage() === 11
                    && $currentWeather->getIcon() === '[ICON]'
                    && $currentWeather->getConditionCode() === 1256;
            }));

        self::assertTrue(
            $this->subject->execute()
        );
    }
}