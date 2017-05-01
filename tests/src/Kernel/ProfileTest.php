<?php

namespace Drupal\Tests\profile\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;
use Drupal\profile\ProfileTestTrait;

/**
 * Tests basic functionality of profiles.
 *
 * @group profile
 */
class ProfileTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity',
    'profile',
    'views',
  ];

  /**
   * Testing demo user 1.
   *
   * @var \Drupal\user\UserInterface
   */
  public $user1;

  /**
   * Testing demo user 2.
   *
   * @var \Drupal\user\UserInterface;
   */
  public $user2;

  /**
   * Profile entity storage.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  public $profileStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('profile');
    $this->installEntitySchema('view');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['profile', 'user']);
    $this->profileStorage = $this->container->get('entity_type.manager')
      ->getStorage('profile');
    $this->user1 = $this->createUser();
    $this->user2 = $this->createUser();
  }

  /**
   * Tests the profile entity and its methods.
   */
  public function testProfile() {
    $types_data = [
      'profile_type_0' => ['label' => $this->randomMachineName()],
      'profile_type_1' => ['label' => $this->randomMachineName()],
    ];

    /** @var \Drupal\profile\Entity\ProfileTypeInterface[] $types */
    $types = [];
    foreach ($types_data as $id => $values) {
      $types[$id] = ProfileType::create(['id' => $id] + $values);
      $types[$id]->save();
    }

    // Create a new profile.
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->profileStorage->create([
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);

    $this->assertEquals($profile->getOwnerId(), $this->user1->id());
    $this->assertEquals($profile->getCreatedTime(), REQUEST_TIME);
    $this->assertEquals($profile->getChangedTime(), REQUEST_TIME);

    // Save the profile.
    $profile->save();
    $this->assertEquals(REQUEST_TIME, $profile->getChangedTime());
    $expected_label = new TranslatableMarkup('@type profile #@id', [
      '@type' => $types['profile_type_0']->label(),
      '@id' => $profile->id(),
    ]);
    $this->assertEquals($expected_label, $profile->label());

    // List profiles for the user and verify that the new profile appears.
    $list = $this->profileStorage->loadByProperties(['uid' => $this->user1->id()]);
    $list_ids = array_keys($list);
    $this->assertEquals($list_ids, [$profile->id()]);

    // Create a second profile.
    $user1_profile1 = $profile;
    $user1_profile = $this->profileStorage->create([
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);
    $user1_profile->save();

    // List profiles for the user and verify that both profiles appear.
    $list = $this->profileStorage->loadByProperties(['uid' => $this->user1->id()]);
    $list_ids = array_keys($list);
    $this->assertEquals($list_ids, [$user1_profile1->id(), $user1_profile->id()]);

    // Delete the second profile and verify that the first still exists.
    $user1_profile->delete();
    $list = $this->profileStorage->loadByProperties(['uid' => $this->user1->id()]);
    $list_ids = array_keys($list);
    $this->assertEquals($list_ids, [$user1_profile1->id()]);

    // Create a profile for the second user.
    $user2_profile1 = $this->profileStorage->create([
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user2->id(),
    ]);
    $user2_profile1->save();

    // Delete the first user and verify that all of its profiles are deleted.
    $this->user1->delete();
    $list = $this->profileStorage->loadByProperties(['uid' => $this->user1->id()]);
    $list_ids = array_keys($list);
    $this->assertEquals($list_ids, []);

    // List profiles for the second user and verify that they still exist.
    $list = $this->profileStorage->loadByProperties(['uid' => $this->user2->id()]);
    $list_ids = array_keys($list);
    $this->assertEquals($list_ids, [$user2_profile1->id()]);
  }

  /**
   * Tests profiles are active by default.
   */
  public function testProfileActive() {
    $profile_type = ProfileType::create([
      'id' => 'test_defaults',
      'label' => 'test_defaults',
    ]);

    // Create new profiles.
    $profile1 = Profile::create([
      'type' => $profile_type->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile1->save();
    $this->assertTrue($profile1->isActive());

    $profile1->setActive(FALSE);
    $profile1->save();

    $this->assertFalse($profile1->isActive());
  }

  /**
   * Tests default profile functionality.
   */
  public function testDefaultProfile() {
    $profile_type = ProfileType::create([
      'id' => 'test_defaults',
      'label' => 'test_defaults',
    ]);

    // Create a new profile.
    $profile1 = Profile::create([
      'type' => $profile_type->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile1->save();

    // Verify that the first profile of this type is default.
    $this->assertTrue($profile1->isDefault());

    // Create a second new profile.
    $profile2 = Profile::create([
      'type' => $profile_type->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile2->setDefault(TRUE);
    $profile2->save();

    $this->assertFalse($this->reloadEntity($profile1)->isDefault());
    $this->assertTrue($this->reloadEntity($profile2)->isDefault());

    $profile1->setDefault(TRUE)->save();
    $this->assertFalse($this->reloadEntity($profile2)->isDefault());
    $this->assertTrue($this->reloadEntity($profile1)->isDefault());

    // Verify that a deactivated profile cannot be the default and that if the
    // current default is disactivated another default is set.
    $profile2->setActive(FALSE);
    $profile2->save();

    $this->assertFalse($this->reloadEntity($profile2)->isDefault());
    $this->assertTrue($this->reloadEntity($profile1)->isDefault());
  }

  /**
   * Tests loading default from storage handler.
   */
  public function testLoadDefaultProfile() {
    $profile_type = ProfileType::create([
      'id' => 'test_defaults',
      'label' => 'test_defaults',
    ]);

    // Create new profiles.
    $profile1 = Profile::create([
      'type' => $profile_type->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile1->setActive(TRUE);
    $profile1->save();
    $profile2 = Profile::create([
      'type' => $profile_type->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile2->setActive(TRUE);
    $profile2->setDefault(TRUE);
    $profile2->save();

    /** @var \Drupal\profile\ProfileStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('profile');

    $default_profile = $storage->loadDefaultByUser($this->user1, $profile_type->id());
    $this->assertEquals($profile2->id(), $default_profile->id());
  }

}
