<?php

namespace Drupal\Tests\yse_views_stratify\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\yse_views_stratify\ViewsStratifyService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit tests for ViewsStratifyService.
 *
 * @group yse_views_stratify
 */
class ViewsStratifyServiceTest extends UnitTestCase {

  /**
   * The views stratify service.
   *
   * @var \Drupal\yse_views_stratify\ViewsStratifyService
   */
  protected $service;

  /**
   * The logger factory mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The request stack mock.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock logger factory and channel.
    $logger_channel = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method('get')
      ->willReturn($logger_channel);

    // Mock request stack.
    $this->requestStack = $this->createMock(RequestStack::class);

    // Create the service.
    $this->service = new ViewsStratifyService(
      $this->loggerFactory,
      $this->requestStack
    );
  }

  /**
   * Tests isExclusiveDisplay method.
   *
   * @covers ::isExclusiveDisplay
   */
  public function testIsExclusiveDisplay() {
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isExclusiveDisplay');
    $method->setAccessible(TRUE);

    // Test positive cases.
    $this->assertTrue($method->invoke($this->service, 'block_exclusive_rows_full'));
    $this->assertTrue($method->invoke($this->service, 'page_exclusive_rows_teaser'));
    $this->assertTrue($method->invoke($this->service, 'attachment_exclusive_rows_header'));

    // Test negative cases.
    $this->assertFalse($method->invoke($this->service, 'block_remainder_rows_full'));
    $this->assertFalse($method->invoke($this->service, 'page_default'));
    $this->assertFalse($method->invoke($this->service, 'embed_stratify_query'));
  }

  /**
   * Tests isRemainderDisplay method.
   *
   * @covers ::isRemainderDisplay
   */
  public function testIsRemainderDisplay() {
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isRemainderDisplay');
    $method->setAccessible(TRUE);

    // Test positive cases.
    $this->assertTrue($method->invoke($this->service, 'block_remainder_rows_full'));
    $this->assertTrue($method->invoke($this->service, 'page_remainder_rows_teaser'));
    $this->assertTrue($method->invoke($this->service, 'attachment_remainder_rows_footer'));

    // Test negative cases.
    $this->assertFalse($method->invoke($this->service, 'block_exclusive_rows_full'));
    $this->assertFalse($method->invoke($this->service, 'page_default'));
    $this->assertFalse($method->invoke($this->service, 'embed_stratify_query'));
  }

  /**
   * Tests validateDisplay method with valid displays.
   *
   * @covers ::validateDisplay
   */
  public function testValidateDisplayValid() {
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('validateDisplay');
    $method->setAccessible(TRUE);

    // These should not throw exceptions.
    $method->invoke($this->service, 'block_exclusive_rows_full');
    $method->invoke($this->service, 'block_remainder_rows_full');
    $method->invoke($this->service, 'page_default');

    // If we get here without exceptions, the test passes.
    $this->assertTrue(TRUE);
  }

  /**
   * Tests validateDisplay method with conflicting display name.
   *
   * @covers ::validateDisplay
   */
  public function testValidateDisplayConflict() {
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('validateDisplay');
    $method->setAccessible(TRUE);

    // This should throw an exception.
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/conflicting stratification markers/');
    $method->invoke($this->service, 'block_exclusive_rows_remainder_rows');
  }

  /**
   * Tests getCacheKey method.
   *
   * @covers ::getCacheKey
   */
  public function testGetCacheKey() {
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('getCacheKey');
    $method->setAccessible(TRUE);

    // Mock a view.
    $view = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $view->method('id')->willReturn('test_view');
    $view->args = [123, 'foo'];

    $cache_key = $method->invoke($this->service, $view);

    // Verify format.
    $this->assertStringStartsWith('stratify:test_view:', $cache_key);
    $this->assertMatchesRegularExpression('/^stratify:test_view:[a-f0-9]{32}$/', $cache_key);
  }

  /**
   * Tests getBaseField method.
   *
   * @covers ::getBaseField
   */
  public function testGetBaseField() {
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('getBaseField');
    $method->setAccessible(TRUE);

    // Test known base tables.
    $this->assertEquals('nid', $method->invoke($this->service, 'node_field_data'));
    $this->assertEquals('uid', $method->invoke($this->service, 'users_field_data'));
    $this->assertEquals('tid', $method->invoke($this->service, 'taxonomy_term_field_data'));
    $this->assertEquals('mid', $method->invoke($this->service, 'media_field_data'));
    $this->assertEquals('cid', $method->invoke($this->service, 'comment_field_data'));

    // Test unknown base table (should default to 'id').
    $this->assertEquals('id', $method->invoke($this->service, 'unknown_table'));
  }

}
