<?php

declare(strict_types=1);

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\PathNormalizer;
use Qubus\Exception\Exception;
use Qubus\Exception\Http\Client\NotFoundException;
use Qubus\FileSystem\FileSystem;

beforeEach(function (): void {
    $this->temporaryDirectory = sys_get_temp_dir()
        . DIRECTORY_SEPARATOR
        . 'qubus-filesystem-'
        . bin2hex(random_bytes(8));
    mkdir($this->temporaryDirectory, 0700, true);
    $this->filesystem = new FileSystem(new InMemoryFilesystemAdapter());
});

afterEach(function (): void {
    if (isset($this->temporaryDirectory)) {
        $this->filesystem->rmdir($this->temporaryDirectory);
    }
});

it('continues to expose the Flysystem API', function (): void {
    $this->filesystem->write('documents/example.txt', 'contents');

    expect($this->filesystem->read('documents/example.txt'))->toBe('contents');
});

it('normalizes paths with the Flysystem default normalizer', function (): void {
    expect($this->filesystem->normalizePath('documents/./archive/../example.txt'))
        ->toBe('documents/example.txt');
});

it('uses a custom path normalizer consistently', function (): void {
    $normalizer = new class implements PathNormalizer {
        public function normalizePath(string $path): string
        {
            return 'normalized-' . $path;
        }
    };
    $filesystem = new FileSystem(new InMemoryFilesystemAdapter(), [], $normalizer);

    $filesystem->write('file.txt', 'contents');

    expect($filesystem->normalizePath('file.txt'))->toBe('normalized-file.txt')
        ->and($filesystem->read('file.txt'))->toBe('contents');
});

it('reads both populated and empty local files', function (): void {
    $populatedFile = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'populated.txt';
    $emptyFile = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'empty.txt';
    file_put_contents($populatedFile, 'contents');
    file_put_contents($emptyFile, '');

    expect($this->filesystem->getContents($populatedFile))->toBe('contents')
        ->and($this->filesystem->getContents($emptyFile))->toBe('');
});

it('returns false when contents cannot be read', function (): void {
    $missingFile = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'missing.txt';

    expect($this->filesystem->getContents($missingFile))->toBeFalse();
});

it('creates nested directories and rejects empty paths', function (): void {
    $nestedDirectory = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'one' . DIRECTORY_SEPARATOR . 'two';

    expect($this->filesystem->mkdir($nestedDirectory))->toBeTrue()
        ->and($nestedDirectory)->toBeDirectory();

    $this->filesystem->mkdir('   ');
})->throws(Exception::class, 'Invalid directory path');

it('lists directories and files when the path has no trailing separator', function (): void {
    $directory = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'listing';
    mkdir($directory . DIRECTORY_SEPARATOR . 'nested', 0700, true);
    file_put_contents($directory . DIRECTORY_SEPARATOR . 'first.txt', 'first');
    file_put_contents($directory . DIRECTORY_SEPARATOR . 'second.txt', 'second');

    expect($this->filesystem->directoryListing($directory))->toBe(['nested'])
        ->and($this->filesystem->directoryListing($directory, 'files'))->toBe(['first.txt', 'second.txt']);
});

it('rejects unsupported directory listing types', function (): void {
    $this->filesystem->directoryListing($this->temporaryDirectory, 'everything');
})->throws(InvalidArgumentException::class, 'expected "dirs" or "files"');

it('reports unreadable directory listings without emitting a type error', function (): void {
    $this->filesystem->directoryListing($this->temporaryDirectory . DIRECTORY_SEPARATOR . 'missing');
})->throws(NotFoundException::class, 'could not be read');

it('does not follow directory symlinks during recursive deletion', function (): void {
    $target = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'target';
    $tree = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'tree';
    mkdir($target);
    mkdir($tree);
    file_put_contents($target . DIRECTORY_SEPARATOR . 'keep.txt', 'keep');

    if (! function_exists('symlink') || ! @symlink($target, $tree . DIRECTORY_SEPARATOR . 'linked-target')) {
        $this->markTestSkipped('Symbolic links are not available in this environment.');
    }

    $this->filesystem->rmdir($tree);

    expect($tree)->not->toBeDirectory()
        ->and($target . DIRECTORY_SEPARATOR . 'keep.txt')->toBeFile();
});

it('refuses to recursively remove the filesystem root', function (): void {
    $this->filesystem->rmdir(DIRECTORY_SEPARATOR);
})->throws(InvalidArgumentException::class, 'Refusing to remove filesystem root');

it('prepends, appends, and replaces existing file contents', function (): void {
    $file = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'mutable.txt';
    file_put_contents($file, 'middle');

    expect($this->filesystem->prepend($file, 'before-'))->toBeTrue()
        ->and(file_get_contents($file))->toBe('before-middle')
        ->and($this->filesystem->append($file, '-after'))->toBeTrue()
        ->and(file_get_contents($file))->toBe('before-middle-after')
        ->and($this->filesystem->update($file, 'replacement'))->toBeTrue()
        ->and(file_get_contents($file))->toBe('replacement');
});

it('does not create missing files during mutation', function (): void {
    $file = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'missing.txt';

    expect($this->filesystem->prepend($file, 'prepend'))->toBeFalse()
        ->and($this->filesystem->append($file, 'append'))->toBeFalse()
        ->and($this->filesystem->update($file, 'update'))->toBeFalse()
        ->and($file)->not->toBeFile();
});

it('handles empty writes to existing files', function (): void {
    $file = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'empty-write.txt';
    file_put_contents($file, 'contents');

    expect($this->filesystem->update($file, ''))->toBeTrue()
        ->and(file_get_contents($file))->toBe('')
        ->and($this->filesystem->append($file, ''))->toBeTrue()
        ->and($this->filesystem->prepend($file, ''))->toBeTrue();
});

it('adds and removes trailing slashes without duplication', function (): void {
    expect($this->filesystem->removeTrailingSlash('path///'))->toBe('path')
        ->and($this->filesystem->removeTrailingSlash('path\\'))->toBe('path')
        ->and($this->filesystem->addTrailingSlash('path\\'))->toBe('path/');
});
