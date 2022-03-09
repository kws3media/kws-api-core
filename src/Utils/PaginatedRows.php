<?php

namespace Kws3\ApiCore\Utils;

use Kws3\ApiCore\Models\Model;

/**
 * Given a $model and a $queryObject, it returns a paginated list of
 * database rows based on $perPage and scoped by the $queryObject.
 *
 * This paginated list can be iterated over transparently like an array,
 * while it lazily loads the rows in each iteration.
 *
 * @package Utils
 */
class PaginatedRows extends Abstracts\PaginatedIterator
{

  protected Model $model;

  protected array $queryObject;

  /**
   * Constructor method
   * @param Model $model - any database model instance
   * @param array $queryObject - object with query parameters compatible with objects used in $model->load() or $model->find()
   * @param int $perPage - number of rows to be returned per page, default: 20
   * @return void
   */
  public function __construct($model, $queryObject, $perPage = 20)
  {

    $this->model = $model;
    $this->queryObject = $queryObject;


    $this->setItemsPerPage($perPage);

    //get the total number of rows available for that specific query
    $totalItems = $this->model->count($this->queryObject);

    $this->setTotalItems($totalItems);
  }

  /**
   * Given a $pageNumber, calculate it's database offset,
   * fetch data from that offset, and then return a Traversable collection
   * @param int $pageNumber
   * @return Traversable
   */
  public function getPage($pageNumber)
  {
    $itemsPerPage = $this->getItemsPerPage();
    $options = [
      'offset' => $pageNumber * $itemsPerPage,
      'limit' => $itemsPerPage
    ];

    $results = $this->model->find($this->queryObject, $options);
    return empty($results) ? [] : $results;
  }
}
