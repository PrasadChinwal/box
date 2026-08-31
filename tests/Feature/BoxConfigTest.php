<?php

namespace PrasadChinwal\Box\Test\Feature;

use PrasadChinwal\Box\BoxServiceProvider;
use PrasadChinwal\Box\Test\TestCase;

class BoxConfigTest extends TestCase
{
    public function test_it_merges_package_configuration_into_the_box_namespace(): void
    {
        $this->assertSame('client_credentials', config('box.auth_method'));
        $this->assertSame(0, config('box.folder_id'));
        $this->assertNull(config('box-config.auth_method'));
    }

    public function test_it_keeps_the_legacy_publish_tag_name(): void
    {
        $this->assertSame('box-config', BoxServiceProvider::CONFIG_TAG);
    }
}
