<?php 

namespace Stafflink\Lib\Mapper;

interface MapperInterface
{

    /**
     * Class constructor
     *
     * @param array $fields
     * @param array $keywords
     */
    public function __construct( $fields, $keywords = [] ) ;

    /**
     * Map out the information
     *
     * @return array
     */
    public function map() : array;
}