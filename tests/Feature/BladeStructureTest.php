<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BladeStructureTest extends TestCase
{
    public function test_blade_folder_hierarchy_matches_the_feu_portal_views(): void
    {
        $expectedDirectories = [
            'admin',
            'admin/reports',
            'auth',
            'auth/passwords',
            'layouts',
            'superadmin',
            'user',
        ];

        $actualDirectories = collect(File::directories(resource_path('views')))
            ->flatMap(function (string $directory): array {
                return [$directory, ...File::directories($directory)];
            })
            ->map(fn (string $directory) => str_replace('\\', '/', str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $directory)))
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedDirectories, $actualDirectories);
    }

    public function test_admin_subject_and_section_module_blades_are_removed(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/admin/sections.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/admin/subjects.blade.php'));
    }
}
