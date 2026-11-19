<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\PasswordReset;
use App\Model\Table\PasswordResetsTable;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class PasswordResetsTableTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
    ];

    private PasswordResetsTable $PasswordResets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->PasswordResets = TableRegistry::getTableLocator()->get('PasswordResets');
    }

    protected function tearDown(): void
    {
        DateTime::setTestNow(null);

        parent::tearDown();
    }

    private function user(int $id = 2): \App\Model\Entity\User
    {
        return TableRegistry::getTableLocator()->get('Users')->get($id);
    }

    public function testOnlyTheHashIsStored(): void
    {
        $token = $this->PasswordResets->issue($this->user(), PasswordReset::PURPOSE_RESET, '10.0.0.1');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertFalse($this->PasswordResets->exists(['token_hash' => $token]));
        $this->assertTrue($this->PasswordResets->exists(['token_hash' => hash('sha256', $token)]));
        $this->assertSame(2, $this->PasswordResets->findUsable($token)?->user->id);
    }

    public function testResetLinkLastsOneHourAndInviteFortyEightHours(): void
    {
        DateTime::setTestNow('2026-11-19 10:00:00');
        $reset = $this->PasswordResets->issue($this->user(2), PasswordReset::PURPOSE_RESET);
        $invite = $this->PasswordResets->issue($this->user(1), PasswordReset::PURPOSE_INVITE);

        DateTime::setTestNow('2026-11-19 10:59:00');
        $this->assertNotNull($this->PasswordResets->findUsable($reset));

        DateTime::setTestNow('2026-11-19 11:01:00');
        $this->assertNull($this->PasswordResets->findUsable($reset));
        $this->assertNotNull($this->PasswordResets->findUsable($invite));

        DateTime::setTestNow('2026-11-21 10:01:00');
        $this->assertNull($this->PasswordResets->findUsable($invite));
    }

    public function testANewLinkInvalidatesThePreviousOne(): void
    {
        $first = $this->PasswordResets->issue($this->user(), PasswordReset::PURPOSE_RESET);
        $second = $this->PasswordResets->issue($this->user(), PasswordReset::PURPOSE_RESET);

        $this->assertNull($this->PasswordResets->findUsable($first));
        $this->assertNotNull($this->PasswordResets->findUsable($second));
    }

    public function testChangingThePasswordInvalidatesPendingLinks(): void
    {
        $user = $this->user();
        $token = $this->PasswordResets->issue($user, PasswordReset::PURPOSE_RESET);

        $users = TableRegistry::getTableLocator()->get('Users');
        $this->assertTrue($users->changePassword($user, 'uma frase longa qualquer', 'uma frase longa qualquer'));

        $this->assertNull($this->PasswordResets->findUsable($token));
        $this->assertNotNull($users->get(2)->password_changed_at);
    }

    public function testRejectedPasswordKeepsErrorsOnTheEntityAndChangesNothing(): void
    {
        $user = $this->user();
        $users = TableRegistry::getTableLocator()->get('Users');

        $this->assertFalse($users->changePassword($user, 'curta', 'outra'));
        $this->assertArrayHasKey('new_password', $user->getErrors());
        $this->assertArrayHasKey('confirm_password', $user->getErrors());
        $this->assertNull($users->get(2)->password_changed_at);
    }

    public function testEmptyOrUnknownTokenIsNotUsable(): void
    {
        $this->assertNull($this->PasswordResets->findUsable(''));
        $this->assertNull($this->PasswordResets->findUsable(str_repeat('a', 64)));
    }
}
