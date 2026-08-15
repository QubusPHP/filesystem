# Qubus FileSystem

Qubus FileSystem is a PHP 8.4+ filesystem component built on [League Flysystem 3](https://flysystem.thephpleague.com/). It provides configured 
adapters for local storage, memory, FTP, SFTP, and Amazon S3, together with a small set of helpers for working 
directly with the host filesystem.

## Requirements

- PHP 8.4 or newer
- The cURL and FTP PHP extensions

The appropriate network credentials and PHP extensions are also required when using S3 or SFTP.

## Installation

```shell
composer require qubus/filesystem
```

## License

Qubus FileSystem is released under the [MIT License](LICENSE.md).

## Additional resources

- [Documentation](https://codefyphp.com/docs/digging-deeper/filesystem/)
- [League Flysystem documentation](https://flysystem.thephpleague.com/)
