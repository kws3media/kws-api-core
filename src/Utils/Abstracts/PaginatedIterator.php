<?php

namespace Kws3\ApiCore\Utils\Abstracts;

use \ArrayAccess;
use \Countable;
use \InvalidArgumentException;
use \Iterator;
use \LogicException;
use \OutOfBoundsException;

/**
 * Provides an iterable list of paginated rows from any data source
 * @package Utils\Abstracts
 */
abstract class PaginatedIterator implements ArrayAccess, Iterator, Countable
{
  /** @var bool */
  protected $shouldCache = false; //increases memory usage, use carefully

  /** @var array */
  protected $cache = [];

  /** @var int */
  protected $index = 0;

  /** @var int */
  private $totalItems = 0;

  /** @var int */
  private $itemsPerPage = 10;

  /**
   * Return the contents of $pageNumber
   * @param int $pageNumber
   * @return mixed
   */
  abstract public function getPage($pageNumber);

  /**
   * Returns the contents from getPage() in the context of an Iterator
   * @param int $page
   * @return Traversable
   * @throws InvalidArgumentException
   * @throws OutOfBoundsException
   */
  public function offsetGet($page)
  {
    if (!is_int($page) || $page < 0) {
      throw new InvalidArgumentException("Index $page must be a positive integer");
    }
    if (!$this->offsetExists($page)) {
      throw new OutOfBoundsException("Index $page is out of bounds");
    }

    if ($this->shouldCache) {
      if (!array_key_exists($page, $this->cache)) {
        $this->cache[$page] = $this->getPage($page);
      }
      return $this->cache[$page];
    }

    return $this->getPage($page);
  }

  // ===========================
  // Convenience methods
  // ===========================

  /**
   * Get total number of items in dataset
   * @return int
   */
  public function getTotalItems()
  {
    return $this->totalItems;
  }

  /**
   * Get number of items per page
   * @return int
   */
  public function getItemsPerPage()
  {
    return $this->itemsPerPage;
  }

  /**
   * Get total number of pages based on how many items there are total,
   * and how many should be returned per page.
   * @return int
   */
  public function getTotalPages()
  {
    return floor($this->totalItems / $this->itemsPerPage);
  }

  // ===========================
  // Implemented interface validations methods
  // ===========================

  public function count(): int
  {
    return $this->getTotalItems();
  }

  public function offsetExists($offset): bool
  {
    return $offset >= 0 && $offset <= $this->getTotalPages();
  }

  //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
  public function offsetSet($offset, $value): void
  {
    throw new LogicException("Cannot set offset");
  }

  //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
  public function offsetUnset($offset): void
  {
    throw new LogicException("Cannot unset offset");
  }

  public function current()
  {
    return $this->offsetGet($this->index);
  }

  public function key()
  {
    return $this->index;
  }

  public function next(): void
  {
    ++$this->index;
  }

  public function rewind(): void
  {
    $this->index = 0;
  }

  public function valid(): bool
  {
    return $this->offsetExists($this->index);
  }

  /**
   * Set the total number of items in dataset
   * @param int $totalItems
   * @return void
   */
  protected function setTotalItems($totalItems)
  {
    $this->totalItems = (int) $totalItems;
  }

  /**
   * Set number of items per page
   * @param int $itemsPerPage
   * @return void
   */
  protected function setItemsPerPage($itemsPerPage)
  {
    $this->itemsPerPage = (int) $itemsPerPage;
  }
}
