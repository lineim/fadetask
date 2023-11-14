<?php
namespace app\tests\Common;

use app\common\toolkit\ArrayHelper;
use app\tests\Base;

class ArrayHelperTest extends Base
{

    public function testParts()
    {
        $array = [
            'name' => 'ok',
            'age' => 10,
            'mobile' => 138,
        ];
        $partKeys = ['name', 'age'];
        $part = ArrayHelper::parts($array, $partKeys);
        $this->assertCount(count($partKeys), $part);
        foreach ($part as $key => $val) {
            $this->assertTrue(in_array($key, $partKeys));
            $this->assertEquals($array[$key], $val);
        }
    }

    public function testHasKeys()
    {
        $array = [
            'name' => 'ok',
            'age' => 10,
            'mobile' => 138,
        ];
        $this->assertTrue(ArrayHelper::hasKeys($array, ['name', 'age']));
        $this->assertTrue(ArrayHelper::hasKeys($array, ['name']));
        $this->assertTrue(ArrayHelper::hasKeys($array, ['name', 'age', 'mobile']));

        $this->assertFalse(ArrayHelper::hasKeys($array, ['not_exist']));
        $this->assertFalse(ArrayHelper::hasKeys($array, ['not_exist', 'name']));
    }

    public function testIndex()
    {
        $array = [
            [
                'id' => 1, 
                'name' => 'lineim',
            ],
            [
                'id' => 2, 
                'name' => 'lineim2',
            ]
        ];
        $indexArray = ArrayHelper::index($array, 'id');
        foreach ($array as $item) {
            $id = $item['id'];
            $this->assertTrue(isset($indexArray[$id]));
            $indexItem = $indexArray[$id];
            $this->assertEquals($item['name'], $indexItem['name']);
        }

        $array = [
            [
                'name' => 'lineim',
            ],
            [
                'id' => 2, 
                'name' => 'lineim2',
            ]
        ];
        $indexArray = ArrayHelper::index($array, 'id');
        $this->assertCount(1, $indexArray);
        $this->assertTrue(isset($indexArray[2]));

        $array = [
            [
                'name' => 'lineim',
            ],
            [
                'name' => 'lineim2',
            ]
        ];
        $indexArray = ArrayHelper::index($array, 'id');
        $this->assertEmpty($indexArray);

        $array = [
           'name',
           'age'
        ];
        $indexArray = ArrayHelper::index($array, 'id');
        $this->assertEmpty($indexArray);
    }

    public function testGroup()
    {
        $array = [
            [
                'id' => 1, 
                'name' => 'lineim',
            ],
            [
                'id' => 2, 
                'name' => 'lineim2',
            ],
            [
                'id' => 2, 
                'name' => 'lineim2',
            ]
        ];
        $groupedArray = ArrayHelper::group($array, 'id');
        $this->assertCount(2, $groupedArray[2]);
        $this->assertCount(1, $groupedArray[1]);
    }

}
