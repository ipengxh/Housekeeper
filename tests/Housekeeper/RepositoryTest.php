<?php namespace Housekeeper\Eloquent;

use Housekeeper\Exceptions\RepositoryException;
use Housekeeper\Contracts\Repository;
use Housekeeper\Contracts\Injection\Basic;
use Housekeeper\Contracts\Injection\Before;
use Housekeeper\Contracts\Injection\After;
use Housekeeper\Contracts\Injection\Reset;
use Housekeeper\Flows\Before;
use Housekeeper\Flows\After;
use Housekeeper\Flows\Reset;
use Mockery as m;

/**
 * Class RepositoryTest
 *
 * @covers  Housekeeper\Repository
 * @author  AaronJan <https://github.com/AaronJan/Housekeeper>
 * @package Housekeeper\Eloquent
 */
class RepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     */
    protected function setUp()
    {

    }

    /**
     *
     */
    public function tearDown()
    {
        m::close();
    }

    /**
     * @covers Housekeeper\Eloquent\initializeSetups::setupExternal
     */
    public function testSetup()
    {
        /**
         * Mock custom Repository class but do not call "__construct" yet,
         * because "setup" method will be called.
         */
        $mockRepository = $this->makeMockRepository(MockSetupRepository::class, false);

        /**
         * Mock a method named "setupTest", when repository initializing, it'll
         * call "setup" method, and "setup" method will call all method that
         * name starting with "setup", so If "setupTest" be called, then
         * "$called" should be true.
         */
        $called = false;
        $mockRepository->shouldReceive('setupTest')
            ->andReturnUsing(function () use (&$called) {
                $called = true;
            });

        /**
         * Call "setup" function to verify that.
         */
        $methodSetup = getUnaccessibleObjectMethod($mockRepository, 'setup');
        $methodSetup->invoke($mockRepository, array());

        $this->assertTrue($called);
    }

    /**
     * @covers Housekeeper\Eloquent\createModelInstance::getNewModelInstance
     */
    public function testModelInstance()
    {
        $mockRepository = m::mock(Repository::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();;

        /**
         * Because in the "model" method we only return a model class name, so
         * we should mock it as hard-dependency.
         */
        $mockModel = $this->makeMockModel('overload:Test\FakeModel');

        /**
         * So in here, we just return the class name of mock model.
         */
        $mockRepository->shouldReceive('model')
            ->andReturn('Test\FakeModel');

        /**
         * Check it.
         */
        $methodModelInstance = getUnaccessibleObjectMethod($mockRepository, 'modelInstance');
        $model               = $methodModelInstance->invoke($mockRepository, array());

        $this->assertInstanceOf('Test\FakeModel', $model);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::freshModel
     */
    public function testFreshModel()
    {
        $mockRepository = $this->makeMockRepository(Repository::class, false);

        /**
         * The model instance should be "null" at first.
         */
        $model = getUnaccessibleObjectPropertyValue($mockRepository, 'model');

        $this->assertNull($model);

        /**
         * Call "freshModel" method to generate a new Model.
         */
        $methodFreshModel = getUnaccessibleObjectMethod($mockRepository, 'freshModel');
        $methodFreshModel->invoke($mockRepository, array());

        /**
         * Check it.
         */
        $model = getUnaccessibleObjectPropertyValue($mockRepository, 'model');

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Model', $model);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::inject
     * @covers Housekeeper\Eloquent\BaseRepository::sortAllInjections
     * @covers Housekeeper\Eloquent\BaseRepository::sortInjection
     */
    public function testInjectResetFlow()
    {
        $mockRepository = $this->makeMockRepository();

        /**
         * Mock an injection that implements ResetInjectionInterface.
         */
        $resetInjection = m::mock(
            Basic::class,
            Reset::class
        );
        $resetInjection->shouldReceive('priority')
            ->andReturn(1);

        /**
         * Call "inject" function, this injection should goes to "reset"
         * injections.
         */
        $methodInject = getUnaccessibleObjectMethod($mockRepository, 'inject');
        $methodInject->invoke($mockRepository, $resetInjection);

        /**
         * Get all injections and check them.
         */
        $injections = getUnaccessibleObjectPropertyValue($mockRepository, 'injections');

        $this->assertCount(1, $injections['reset']);
        $this->assertEquals($resetInjection, $injections['reset'][0]);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::inject
     * @covers Housekeeper\Eloquent\BaseRepository::sortAllInjections
     * @covers Housekeeper\Eloquent\BaseRepository::sortInjection
     */
    public function testInjectBeforeFlow()
    {
        $mockRepository = $this->makeMockRepository();

        /**
         * Mock an injection that implements AfterInjectionInterface.
         */
        $beforeInjection = m::mock(
            Basic::class,
            Before::class
        );
        $beforeInjection->shouldReceive('priority')
            ->andReturn(1);

        /**
         * Call "inject" function, this injection should goes to "before"
         * injections.
         */
        $methodInject = getUnaccessibleObjectMethod($mockRepository, 'inject');
        $methodInject->invoke($mockRepository, $beforeInjection);

        /**
         * Get all injections and check them.
         */
        $injections = getUnaccessibleObjectPropertyValue($mockRepository, 'injections');

        $this->assertCount(1, $injections['before']);
        $this->assertEquals($beforeInjection, $injections['before'][0]);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::inject
     * @covers Housekeeper\Eloquent\BaseRepository::sortAllInjections
     * @covers Housekeeper\Eloquent\BaseRepository::sortInjection
     */
    public function testInjectAfterFlow()
    {
        $mockRepository = $this->makeMockRepository();

        /**
         * Mock an injection that implements AfterInjectionInterface.
         */
        $afterInjection = m::mock(
            Basic::class,
            After::class
        );
        $afterInjection->shouldReceive('priority')
            ->andReturn(1);

        /**
         * Call "inject" function, this injection should goes to "after"
         * injections.
         */
        $methodInject = getUnaccessibleObjectMethod($mockRepository, 'inject');
        $methodInject->invoke($mockRepository, $afterInjection);

        /**
         * Get all injections and check them.
         */
        $injections = getUnaccessibleObjectPropertyValue($mockRepository, 'injections');

        $this->assertCount(1, $injections['after']);
        $this->assertEquals($afterInjection, $injections['after'][0]);
    }

    /**
     * @expectedException \Housekeeper\Exceptions\RepositoryException
     */
    public function testInjectException()
    {
        $mockRepository = $this->makeMockRepository();

        /**
         * Mock an injection that implements basic InjectionInterface, but
         * without any real process injection interface, so it should makes an
         * exception.
         */
        $uselessInjection = m::mock(
            Basic::class
        );
        $uselessInjection->shouldReceive('priority')
            ->andReturn(1);

        /**
         * Call "inject" function to verify that.
         */
        $methodInject = getUnaccessibleObjectMethod($mockRepository, 'inject');
        $methodInject->invoke($mockRepository, $uselessInjection);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::reset
     */
    public function testReset()
    {
        $mockRepository = $this->makeMockRepository();

        /**
         * These should be "true" after tests completed.
         */
        $freshModelCalled = false;
        $injectionCalled  = false;

        /**
         * Mock a reset injection.
         */
        $resetInjection = m::mock(
            Basic::class,
            Reset::class
        );
        $resetInjection->shouldReceive('priority')
            ->andReturn(1);
        $resetInjection->shouldReceive('handle')
            ->andReturnUsing(function () use (&$injectionCalled) {
                $injectionCalled = true;
            });

        /**
         * When "reset" be called,  it should call "freshModel" function.
         */
        $mockRepository->shouldReceive('freshModel')
            ->andReturnUsing(function () use (&$freshModelCalled) {
                $freshModelCalled = true;
            });

        /**
         * Bind reset event handler directly.
         */
        setUnaccessibleObjectPropertyValue(
            $mockRepository,
            'injections',
            [
                'reset'  => [$resetInjection],
                'before' => [],
                'after'  => [],
            ]
        );

        /**
         * Mock an Action.
         */
        $mockAction = $this->makeMockAction();

        //Call "reset"
        $mockRepository->reset($mockAction);

        $this->assertTrue($injectionCalled);
        $this->assertTrue($freshModelCalled);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::before
     */
    public function testBefore()
    {
        $mockRepository = $this->makeMockRepository();

        /**
         * These should be "true" after tests completed.
         */
        $injectionCalled = false;

        /**
         * Mock a before injection.
         */
        $beforeInjection = m::mock(
            Basic::class,
            Before::class
        );
        $beforeInjection->shouldReceive('priority')
            ->andReturn(1);
        $beforeInjection->shouldReceive('handle')
            ->andReturnUsing(function ($flow) use (&$injectionCalled) {
                $injectionCalled = true;
            });

        /**
         * Bind before injection directly.
         */
        setUnaccessibleObjectPropertyValue(
            $mockRepository,
            'injections',
            [
                'reset'  => [],
                'before' => [$beforeInjection],
                'after'  => [],
            ]
        );

        /**
         * Mock an Action.
         */
        $mockAction = $this->makeMockAction();

        //Call "before"
        $methodBefore = getUnaccessibleObjectMethod($mockRepository, 'before');
        $methodBefore->invoke($mockRepository, $mockAction);

        $this->assertTrue($injectionCalled);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::after
     */
    public function testAfter()
    {
        $mockRepository = $this->makeMockRepository();

        /**
         * These should be "true" after tests completed.
         */
        $InjectionCalled = false;

        /**
         * Mock a after injection.
         */
        $afterInjection = m::mock(
            Basic::class,
            After::class
        );
        $afterInjection->shouldReceive('priority')
            ->andReturn(1);
        $afterInjection->shouldReceive('handle')
            ->andReturnUsing(function () use (&$InjectionCalled) {
                $InjectionCalled = true;
            });

        /**
         * Bind after injection directly.
         */
        setUnaccessibleObjectPropertyValue(
            $mockRepository,
            'injections',
            [
                'reset'  => [],
                'before' => [],
                'after'  => [$afterInjection],
            ]
        );

        /**
         * Mock an Action.
         */
        $mockAction = $this->makeMockAction();

        //Call "after"
        $methodAfter = getUnaccessibleObjectMethod($mockRepository, 'after');
        $methodAfter->invoke($mockRepository, $mockAction, '');

        $this->assertTrue($InjectionCalled);
    }

    /**
     * @coversNothing
     */
    public function testWhenBeforeHasReturn()
    {
        $mockRepository = $this->makeMockRepository();

        /**
         * These should be "true" after tests completed.
         */
        $closureCalled      = false;
        $beforeMethodCalled = false;
        $afterMethodCalled  = false;
        $resetMethodCalled  = false;

        $closure = function () use (&$closureCalled) {
            $closureCalled = true;
        };

        $mockRepository->shouldReceive('before')
            ->andReturnUsing(function () use (&$beforeMethodCalled) {
                $beforeMethodCalled = true;

                $mockFlow = m::mock('Housekeeper\Flows\Before');
                $mockFlow->shouldReceive('hasReturn')
                    ->andReturn(true);
                $mockFlow->shouldReceive('getReturn')
                    ->andReturn('good');
                $mockFlow->shouldReceive('handle')
                    ->andReturnNull();

                return $mockFlow;
            });

        $mockRepository->shouldReceive('after')
            ->andReturnUsing(function ($methodName, $result) use (&$afterMethodCalled) {
                $afterMethodCalled = true;

                $mockFlow = m::mock('Housekeeper\Flows\After');
                $mockFlow->shouldReceive('getReturn')
                    ->andReturn('bad');
                $mockFlow->shouldReceive('handle')
                    ->andReturnNull();

                return $mockFlow;
            });

        $mockRepository->shouldReceive('reset')
            ->andReturnUsing(function () use (&$resetMethodCalled) {
                $resetMethodCalled = true;
            });

        /**
         * Mock an Action.
         */
        $mockAction = $this->makeMockAction();

        /**
         * Call "wrap" method.
         */
        $methodWrap = getUnaccessibleObjectMethod($mockRepository, 'wrap');
        $result     = $methodWrap->invoke($mockRepository, $closure, $mockAction);

        $this->assertFalse($closureCalled);
        $this->assertTrue($beforeMethodCalled);
        $this->assertFalse($afterMethodCalled);
        $this->assertFalse($resetMethodCalled);

        $this->assertEquals('good', $result);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::wrap
     */
    public function testWrap()
    {
        $mockRepository = $this->makeMockRepository();

        /**
         * These should be "true" after tests completed.
         */
        $closureCalled      = false;
        $beforeMethodCalled = false;
        $afterMethodCalled  = false;
        $resetMethodCalled  = false;

        $closure = function () use (&$closureCalled) {
            $closureCalled = true;

            return 1;
        };

        $mockRepository->shouldReceive('before')
            ->andReturnUsing(function () use (&$beforeMethodCalled) {
                $beforeMethodCalled = true;

                $mockFlow = m::mock('Housekeeper\Flows\Before');
                $mockFlow->shouldReceive('hasReturn')
                    ->andReturn(false);
                $mockFlow->shouldReceive('handle')
                    ->andReturnNull();

                return $mockFlow;
            });

        $mockRepository->shouldReceive('after')
            ->andReturnUsing(function ($methodName, $result) use (&$afterMethodCalled) {
                $afterMethodCalled = true;

                $mockFlow = m::mock('Housekeeper\Flows\After');
                $mockFlow->shouldReceive('getReturn')
                    ->andReturn($result . 'good');
                $mockFlow->shouldReceive('handle')
                    ->andReturnNull();

                return $mockFlow;
            });

        $mockRepository->shouldReceive('reset')
            ->andReturnUsing(function () use (&$resetMethodCalled) {
                $resetMethodCalled = true;
            });

        /**
         * Mock an Action.
         */
        $mockAction = $this->makeMockAction();

        /**
         * Call "wrap" method.
         */
        $methodWrap = getUnaccessibleObjectMethod($mockRepository, 'wrap');
        $result     = $methodWrap->invoke($mockRepository, $closure, $mockAction);

        $this->assertTrue($closureCalled);
        $this->assertTrue($beforeMethodCalled);
        $this->assertTrue($afterMethodCalled);
        $this->assertTrue($resetMethodCalled);

        $this->assertEquals('1good', $result);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::sortInjection
     */
    public function testSortInjection()
    {
        $mockRepository = $this->makeMockRepository();

        /**
         * Mock three injections to test sorting, equal, lesser and greater.
         */
        $mockInjection_1 = m::mock('Housekeeper\Contracts\Injection\InjectionInterface');
        $mockInjection_1->shouldReceive('priority')
            ->andReturn('1');

        $mockInjection_2 = m::mock('Housekeeper\Contracts\Injection\InjectionInterface');
        $mockInjection_2->shouldReceive('priority')
            ->andReturn('2');

        $mockInjection_3 = m::mock('Housekeeper\Contracts\Injection\InjectionInterface');
        $mockInjection_3->shouldReceive('priority')
            ->andReturn('1');

        /**
         * Get the "sort" method.
         */
        $methodSortInjection = getUnaccessibleObjectMethod($mockRepository, 'sortInjection');

        $this->assertEquals(
            - 1,
            $methodSortInjection->invoke($mockRepository, $mockInjection_1, $mockInjection_2)
        );

        $this->assertEquals(
            1,
            $methodSortInjection->invoke($mockRepository, $mockInjection_2, $mockInjection_1)
        );

        $this->assertEquals(
            0,
            $methodSortInjection->invoke($mockRepository, $mockInjection_1, $mockInjection_1)
        );
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::addCondition
     * @covers Housekeeper\Eloquent\BaseRepository::getConditions
     */
    public function testConditions()
    {
        $mockRepository = $this->makeMockRepository();

        $mockRepository->addCondition('where', [
            'name' => 'Aaron',
        ]);

        $mockRepository->addCondition('with', [
            'article',
        ]);

        $conditions = $mockRepository->getConditions();

        $this->assertEquals([
            [
                'where' => [
                    'name' => 'Aaron',
                ],
            ],
            [
                'with' => [
                    'article',
                ],
            ],
        ], $conditions);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::all
     */
    public function testAll()
    {
        $mockRepository = $this->makeMockRepository();

        $result = $mockRepository->all();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $result);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::find
     */
    public function testFind()
    {
        /**
         * Hand-mock a repository, then we pass it customized model specific for
         * this test.
         */
        $mockRepository = m::mock('Housekeeper\Eloquent\BaseRepository');
        $mockRepository->makePartial()
            ->shouldAllowMockingProtectedMethods();

        /**
         * Mock a customize model.
         */
        $mockModel = $this->makeMockModel();

        $mockModel->shouldReceive('findOrFail')
            ->with(3, ['*'])
            ->andReturn(m::mock('Illuminate\Database\Eloquent\Model'));

        /**
         * Inject the mock model.
         */
        $mockRepository->shouldReceive('modelInstance')
            ->andReturnUsing(function () use ($mockModel) {
                return $mockModel;
            });

        /**
         * Call the constructor of mock repository.
         */
        $mockRepository->__construct($this->mockApplication());

        /**
         * Check "paginate".
         */
        $result = $mockRepository->find(3);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Model', $result);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::paginate
     */
    public function testPaginate()
    {
        /**
         * Hand-mock a repository, then we pass it customized model specific for
         * this test.
         */
        $mockRepository = m::mock('Housekeeper\Eloquent\BaseRepository');
        $mockRepository->makePartial()
            ->shouldAllowMockingProtectedMethods();

        /**
         * Mock a customize model.
         */
        $mockModel = $this->makeMockModel();

        $mockModel->shouldReceive('paginate')
            ->with(15, ['*'])
            ->andReturn(m::mock('Illuminate\Contracts\Pagination\LengthAwarePaginator'));

        /**
         * Inject the mock model.
         */
        $mockRepository->shouldReceive('modelInstance')
            ->andReturnUsing(function () use ($mockModel) {
                return $mockModel;
            });

        /**
         * Call the constructor of mock repository.
         */
        $mockRepository->__construct($this->mockApplication());

        /**
         * Check "paginate".
         */
        $result = $mockRepository->paginate(15);

        $this->assertInstanceOf('Illuminate\Contracts\Pagination\LengthAwarePaginator', $result);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::with
     */
    public function testWith()
    {
        /**
         * These should be "true" after tests completed.
         */
        $addConditionCalled = false;
        $modelWithCalled    = false;

        /**
         * Hand-mock a repository, then we pass it customized model specific for
         * this test.
         */
        $mockRepository = m::mock('Housekeeper\Eloquent\BaseRepository');
        $mockRepository->makePartial()
            ->shouldAllowMockingProtectedMethods();

        /**
         * Mock `addConditon` method.
         */
        $mockRepository->shouldReceive('addCondition')
            ->with('with', 'article')
            ->andReturnUsing(function () use (&$addConditionCalled) {
                $addConditionCalled = true;
            });

        /**
         * Mock a customize model.
         */
        $mockModel = $this->makeMockModel();

        $mockModel->shouldReceive('with')
            ->with('article')
            ->andReturnUsing(function () use (&$modelWithCalled) {
                $modelWithCalled = true;
            });

        /**
         * Inject the mock model.
         */
        $mockRepository->shouldReceive('modelInstance')
            ->andReturnUsing(function () use ($mockModel) {
                return $mockModel;
            });

        /**
         * Call the constructor of mock repository.
         */
        $mockRepository->__construct($this->mockApplication());

        /**
         * Check "with".
         */
        $result = $mockRepository->with('article');

        $this->assertTrue($addConditionCalled);
        $this->assertTrue($modelWithCalled);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::applyOrder
     */
    public function testApplyOrder()
    {
        /**
         * These should be "true" after tests completed.
         */
        $orderByCalled      = false;
        $modelOrderByCalled = false;

        /**
         * Hand-mock a repository, then we pass it customized model specific for
         * this test.
         */
        $mockRepository = m::mock('Housekeeper\Eloquent\BaseRepository');
        $mockRepository->makePartial()
            ->shouldAllowMockingProtectedMethods();

        /**
         * Mock `addConditon` method.
         */
        $mockRepository->shouldReceive('addCondition')
            ->with('order', ['age', 'asc'])
            ->andReturnUsing(function () use (&$orderByCalled) {
                $orderByCalled = true;
            });

        /**
         * Mock a customize model.
         */
        $mockModel = $this->makeMockModel();

        $mockModel->shouldReceive('orderBy')
            ->with('age', 'asc')
            ->andReturnUsing(function () use (&$modelOrderByCalled) {
                $modelOrderByCalled = true;
            });

        /**
         * Inject the mock model.
         */
        $mockRepository->shouldReceive('modelInstance')
            ->andReturnUsing(function () use ($mockModel) {
                return $mockModel;
            });

        /**
         * Call the constructor of mock repository.
         */
        $mockRepository->__construct($this->mockApplication());

        /**
         * Check "with".
         */
        $result = $mockRepository->applyOrder('age', 'asc');

        $this->assertTrue($orderByCalled);
        $this->assertTrue($modelOrderByCalled);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::applyWhere
     */
    public function testApplyWhereClosure()
    {
        /**
         * These should be "true" after tests completed.
         */
        $whereCalled = false;

        /**
         * Closure functions that pass to `applyWhere`
         */
        $whereClosure = function () {
        };

        /**
         * Hand-mock a repository, then we pass it customized model specific for
         * this test.
         */
        $mockRepository = m::mock('Housekeeper\Eloquent\BaseRepository');
        $mockRepository->makePartial()
            ->shouldAllowMockingProtectedMethods();

        /**
         * Mock a customize model.
         */
        $mockModel = $this->makeMockModel();

        $mockModel->shouldReceive('where')
            ->once()
            ->ordered()
            ->with($whereClosure)
            ->andReturnUsing(function () use (&$whereCalled) {
                $whereCalled = true;
            });

        /**
         * Inject the mock model.
         */
        $mockRepository->shouldReceive('modelInstance')
            ->andReturnUsing(function () use ($mockModel) {
                return $mockModel;
            });

        /**
         * Call the constructor of mock repository.
         */
        $mockRepository->__construct($this->mockApplication());

        /**
         * Check "applyWhere".
         */
        $mockRepository->applyWhere([
            $whereClosure,
        ]);

        $this->assertTrue($whereCalled);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::applyWhere
     */
    public function testApplyWhereCondition()
    {
        /**
         * These should be "true" after tests completed.
         */
        $firstWhereCalled  = false;
        $secondWhereCalled = false;

        /**
         * Hand-mock a repository, then we pass it customized model specific for
         * this test.
         */
        $mockRepository = m::mock('Housekeeper\Eloquent\BaseRepository');
        $mockRepository->makePartial()
            ->shouldAllowMockingProtectedMethods();

        /**
         * Mock a customize model.
         */
        $mockModel = $this->makeMockModel();

        $mockModel->shouldReceive('where')
            ->once()
            ->ordered()
            ->with('name', '!=', 'Aaron')
            ->andReturnUsing(function () use (&$firstWhereCalled, &$mockModel) {
                $firstWhereCalled = true;

                return $mockModel;
            });

        $mockModel->shouldReceive('where')
            ->once()
            ->ordered()
            ->with('age', '<', '5')
            ->andReturnUsing(function () use (&$secondWhereCalled) {
                $secondWhereCalled = true;
            });

        /**
         * Inject the mock model.
         */
        $mockRepository->shouldReceive('modelInstance')
            ->andReturnUsing(function () use ($mockModel) {
                return $mockModel;
            });

        /**
         * Call the constructor of mock repository.
         */
        $mockRepository->__construct($this->mockApplication());

        /**
         * Check "applyWhere".
         */
        $mockRepository->applyWhere([
            ['name', '!=', 'Aaron'],
            ['age', '<', '5'],
        ]);

        $this->assertTrue($firstWhereCalled);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::applyWhere
     */
    public function testApplyWhereEqual()
    {
        /**
         * These should be "true" after tests completed.
         */
        $firstWhereCalled  = false;
        $secondWhereCalled = false;

        /**
         * Hand-mock a repository, then we pass it customized model specific for
         * this test.
         */
        $mockRepository = m::mock('Housekeeper\Eloquent\BaseRepository');
        $mockRepository->makePartial()
            ->shouldAllowMockingProtectedMethods();

        /**
         * Mock a customize model.
         */
        $mockModel = $this->makeMockModel();

        $mockModel->shouldReceive('where')
            ->once()
            ->ordered()
            ->with('name', '=', 'Aaron')
            ->andReturnUsing(function () use (&$firstWhereCalled, &$mockModel) {
                $firstWhereCalled = true;

                return $mockModel;
            });

        $mockModel->shouldReceive('where')
            ->once()
            ->ordered()
            ->with('age', '=', '5')
            ->andReturnUsing(function () use (&$secondWhereCalled) {
                $secondWhereCalled = true;
            });

        /**
         * Inject the mock model.
         */
        $mockRepository->shouldReceive('modelInstance')
            ->andReturnUsing(function () use ($mockModel) {
                return $mockModel;
            });

        /**
         * Call the constructor of mock repository.
         */
        $mockRepository->__construct($this->mockApplication());

        /**
         * Check "applyWhere".
         */
        $mockRepository->applyWhere([
            'name' => 'Aaron',
            'age'  => '5',
        ]);

        $this->assertTrue($firstWhereCalled);
    }

    /**
     * @covers Housekeeper\Eloquent\BaseRepository::findByField
     */
    public function testFindByField()
    {
        /**
         * These should be "true" after tests completed.
         */
        $modelWhereCalled = false;
        $BuilderGetCalled = false;

        /**
         * Hand-mock a repository, then we pass it customized model specific for
         * this test.
         */
        $mockRepository = m::mock('Housekeeper\Eloquent\BaseRepository');
        $mockRepository->makePartial()
            ->shouldAllowMockingProtectedMethods();

        /**
         *
         */
        $mockBuilder = m::mock('Illuminate\Database\Eloquent\Builder');
        $mockBuilder->shouldReceive('get')
            ->with(['id'])
            ->andReturnUsing(function () use (&$BuilderGetCalled) {
                $BuilderGetCalled = true;
            });

        /**
         * Mock a customize model.
         */
        $mockModel = $this->makeMockModel();

        $mockModel->shouldReceive('where')
            ->with('name', '=', 'Aaron')
            ->andReturnUsing(function () use (&$modelWhereCalled, $mockBuilder) {
                $modelWhereCalled = true;

                return $mockBuilder;
            });

        /**
         * Inject the mock model.
         */
        $mockRepository->shouldReceive('modelInstance')
            ->andReturnUsing(function () use ($mockModel) {
                return $mockModel;
            });

        /**
         * Call the constructor of mock repository.
         */
        $mockRepository->__construct($this->mockApplication());

        /**
         * Check "with".
         */
        $result = $mockRepository->findByField('name', 'Aaron', ['id']);

        $this->assertTrue($modelWhereCalled);
        $this->assertTrue($BuilderGetCalled);
    }


    // ========================================================================

    /**
     * @param string $class
     * @param bool   $concrete
     * @return Repository|m\MockInterface
     */
    protected function makeMockRepository($class = 'Housekeeper\Eloquent\BaseRepository', $concrete = true)
    {
        /**
         * Setup some hints for variables.
         *
         * @var \Housekeeper\Eloquent\Repository|\Mockery\MockInterface $mockRepository
         */

        /**
         * Mock Repository
         */
        $mockRepository = m::mock($class);
        $mockRepository->makePartial()
            ->shouldAllowMockingProtectedMethods();

        /**
         * Override "makeModelInstance" method, returns a mock model.
         */
        $mockRepository->shouldReceive('modelInstance')
            ->andReturnUsing(function () {
                return $this->makeMockModel();
            });

        /**
         * Once we mocked "makeModel" method, we can safely Re-concreting
         * Repository object.
         */
        if ($concrete) {
            $mockRepository->__construct($this->mockApplication());
        }

        return $mockRepository;
    }

    /**
     * @return m\MockInterface|\Illuminate\Contracts\Foundation\Application
     */
    protected function mockApplication()
    {
        /**
         * Mock "Config" instance.
         */
        $mockConfig = m::mock('Illuminate\Config\Repository');

        $mockConfig->shouldReceive('get')
            ->andReturnNull();

        /**
         * Mock "Application".
         */
        $mockApplication = m::mock('Illuminate\Contracts\Foundation\Application');

        $mockApplication->shouldReceive('config')
            ->andReturn([]);

        $mockApplication->shouldReceive('make')
            ->with('config')
            ->andReturn($mockConfig);

        $mockApplication->shouldReceive('make')
            ->with()
            ->andReturn([]);

        return $mockApplication;
    }

    /**
     * @param string $class
     * @return m\MockInterface
     */
    protected function makeMockModel($class = 'Illuminate\Database\Eloquent\Model')
    {
        $mock = m::mock($class);

        $mock->shouldReceive('get')
            ->andReturn(m::mock('Illuminate\Database\Eloquent\Collection'));

        return $mock;
    }

    /**
     * @return m\MockInterface
     */
    protected function makeMockAction()
    {
        $mock = m::mock('Housekeeper\Action');

        $mock->shouldReceive('getArguments')
            ->andReturn([]);

        $mock->shouldReceive('getMethodName')
            ->andReturn('fake');

        return $mock;
    }

}

// ============================================================================

class MockBasic implements Basic, Reset

{

    public function priority()
    {
        return 1;
    }

    public function handle(Reset $flow)
    {

    }

}

/**
 * Class MockSetupRepository
 *
 * @package Housekeeper\Eloquent
 */
class MockSetupRepository extends Repository
{

    /**
     *
     */
    protected function model()
    {

    }

    /**
     *
     */
    protected function setupTest()
    {
        $mockInjection = new MockBasic();

        $this->inject($mockInjection);
    }

}