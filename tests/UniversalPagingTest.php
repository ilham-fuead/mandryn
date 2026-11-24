<?php

namespace Tests;

use ConnectionDetail;
use DBQuery;
use IPagingType;
use PagingInfo;
use PHPUnit\Framework\TestCase;
use UniversalPaging;

/**
 * Regression coverage for UniversalPaging's page-property contract.
 *
 * The pager's MANUAL mode exists so callers can avoid an expensive COUNT(*).
 * That only works if setPageProperty() is treated as authoritative — a later
 * getPagingInfo() call must not fall through to initPageProperty() and recount
 * the whole result set.
 */
final class UniversalPagingTest extends TestCase
{
    public function testGetPagingInfoDoesNotRecountAfterSetPageProperty(): void
    {
        $pager = $this->makePager();
        $pager->setSQLStatement('SELECT id, name FROM users');

        $info = new PagingInfo();
        $info->totalRow = 1234;
        $info->totalPage = 124;
        $pager->setPageProperty($info);

        $pager->getPagingInfo();

        $this->assertSame(
            0,
            $pager->initPagePropertyCalls,
            'setPageProperty() must suppress the recalculation performed by getPagingInfo().'
        );
    }

    public function testSetPagePropertyValuesAreReturnedVerbatim(): void
    {
        $pager = $this->makePager();
        $pager->setSQLStatement('SELECT id, name FROM users');
        $pager->setPagingProperty(IPagingType::MANUAL, 10);

        $info = new PagingInfo();
        $info->totalRow = 1234;
        $info->totalPage = 124;
        $pager->setPageProperty($info);

        $result = $pager->getPagingInfo();

        $this->assertSame(1234, $result->totalRow);
        $this->assertSame(124, $result->totalPage);
        $this->assertSame(10, $result->totalRowPerPaging);
    }

    /**
     * Control case: proves the probe above is not vacuous. When the caller does
     * not supply totals, the pager must still initialise page properties.
     */
    public function testGetPagingInfoInitializesWhenTotalsWereNotSupplied(): void
    {
        $pager = $this->makePager();
        $pager->setSQLStatement('SELECT id, name FROM users');
        $pager->setPagingProperty(IPagingType::MANUAL, 10);

        $pager->getPagingInfo();

        $this->assertSame(
            1,
            $pager->initPagePropertyCalls,
            'Without setPageProperty() the pager must still initialise page properties.'
        );
    }

    private function makePager(): PagingProbe
    {
        return new PagingProbe(new InertConnectionSource());
    }
}

/**
 * Records whether the pager tried to recalculate page properties. The real
 * implementation opens a database connection, so overriding it keeps the test
 * hermetic while still observing the code path that matters.
 */
final class PagingProbe extends UniversalPaging
{
    public int $initPagePropertyCalls = 0;

    protected function initPageProperty()
    {
        $this->initPagePropertyCalls++;

        return null;
    }

    protected function performTaskOnEachPage(DBQuery $DBQueryObj, $startRowIndex, $lastRowIndex): void
    {
    }

    protected function handleTaskOnNoPaging(): void
    {
    }
}

/**
 * Satisfies UniversalPaging's type requirement without opening a connection:
 * the real DBQuery constructor connects on construction and calls exit() on
 * failure, which would terminate the test process instead of failing a test.
 */
final class InertConnectionSource extends DBQuery
{
    public function __construct()
    {
    }

    public function getConnectionDetail()
    {
        return new ConnectionDetail('localhost', 'user', 'pass', 'testdb');
    }

    public function __destruct()
    {
    }
}
