<?php

namespace Stafflink\Lib\Mapper;

class Mapper
{
    /**
     * Saved Keywords for the mapper
     *
     * @var array
     */
    private $keywords;

    /**
     * Fields being passed to the mapper
     *
     * @var array
     */
    private $fields;

    /**
     * Collection for the mapped fields
     *
     * @var array
     */
    private $mappedFields;

    /**
     * Class Constructor
     *
     * @param array $fields
     * @param array $keywords
     */
    public function __construct( $fields, $keywords = [] )
    {
        $this->fields   = $fields;
        $keys           = [ 
            'email'      => [ 
                'email_address',
                'emailaddress',
            ],
            'first_name' => [ 
                'first_name',
                'firstname',
            ],
            'last_name'  => [ 
                'last_name',
                'lastname',
            ],
            'mobile' => [
                'contact_number',
                'phone_number',
                'mobile'
            ]
        ];
        $this->keywords = empty( $keywords )
            ? $keys : $keywords;
    }

    /**
     * Automapped
     *
     * @return void
     */
    public function autoMap()
    {

    }

    /**
     * Map out the information
     *
     * @return array
     */
    public function map() : array
    {

    }
}