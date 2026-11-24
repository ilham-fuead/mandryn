<?php

namespace Tests\Mcp;

use Mandryn\db\MagicObject;
use PHPUnit\Framework\TestCase;

final class MagicObjectTest extends TestCase
{
    public function testConstructorHydratesFromArray(): void
    {
        $o = new MagicObject(['id' => 1, 'name' => 'Ilham']);

        self::assertSame(1, $o->id);
        self::assertSame('Ilham', $o->name);
        self::assertSame(2, $o->count());
    }

    public function testMagicSetAndGet(): void
    {
        $o = new MagicObject();

        $o->email = 'ilham@example.com';

        self::assertSame('ilham@example.com', $o->email);
        self::assertTrue(isset($o->email));
        self::assertFalse(isset($o->missing));
    }

    public function testHasRemoveAndSet(): void
    {
        $o = new MagicObject(['a' => 1]);

        self::assertTrue($o->has('a'));
        self::assertFalse($o->has('b'));

        $o->set('b', 2)->remove('a');

        self::assertFalse($o->has('a'));
        self::assertSame(2, $o->get('b'));
    }

    public function testFromArrayWithColumnMap(): void
    {
        $o = MagicObject::fromArray(
            ['user_id' => 7, 'full_name' => 'Aina'],
            ['user_id' => 'id', 'full_name' => 'name'],
        );

        self::assertSame(7, $o->id);
        self::assertSame('Aina', $o->name);
        self::assertFalse(isset($o->user_id));
    }

    public function testFromRowIsAlias(): void
    {
        $o = MagicObject::fromRow(['x' => 42]);

        self::assertSame(42, $o->x);
    }

    public function testHydrate(): void
    {
        $o = new MagicObject(['id' => 1]);

        $o->hydrate(['name' => 'Zaki', 'active' => true]);

        self::assertSame(1, $o->id);
        self::assertSame('Zaki', $o->name);
        self::assertTrue($o->active);
    }

    public function testApplyColumnMap(): void
    {
        $o = new MagicObject(['snake_case' => 'value']);

        $o->applyColumnMap(['snake_case' => 'camelCase']);

        self::assertSame('value', $o->camelCase);
        self::assertFalse(isset($o->snake_case));
    }

    public function testApplyFilterDefinition(): void
    {
        $o = new MagicObject(['age' => '30', 'email' => 'a@b.com']);

        $o->applyFilterDefinition([
            'age' => FILTER_VALIDATE_INT,
            'email' => FILTER_VALIDATE_EMAIL,
        ]);

        self::assertSame(30, $o->age);
        self::assertSame('a@b.com', $o->email);
    }

    public function testToArrayAndJsonSerialize(): void
    {
        $o = new MagicObject(['id' => 1, 'name' => 'Lia']);

        self::assertSame(['id' => 1, 'name' => 'Lia'], $o->toArray());
        self::assertSame('{"id":1,"name":"Lia"}', $o->getJsonString());
        self::assertSame(['id' => 1, 'name' => 'Lia'], $o->jsonSerialize());
    }

    public function testUnset(): void
    {
        $o = new MagicObject(['a' => 1]);

        unset($o->a);

        self::assertFalse(isset($o->a));
        self::assertNull($o->a);
    }

    public function testGetDefault(): void
    {
        $o = new MagicObject();

        self::assertSame('fallback', $o->get('missing', 'fallback'));
    }

    public function testCount(): void
    {
        $o = new MagicObject(['a' => 1, 'b' => 2]);

        self::assertSame(2, $o->count());
    }
}
