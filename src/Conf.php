<?php declare(strict_types=1);

namespace AP\Conf;

use AP\ErrorNode\Errors;
use AP\ErrorNode\ThrowableErrors;
use AP\Logger\Log;
use AP\Scheme\ToObject;
use AP\Scheme\Validation;
use RuntimeException;
use Throwable;

/**
 * @template T
 */
class Conf
{
    protected static array $conf = [];

    public function __construct(
        readonly private array $directories
    )
    {
    }

    protected function array_by_php_file(string $name): array
    {
        $result = [];
        foreach ($this->directories as $directory) {
            $file = $directory . "/$name.php";
            if (file_exists($file)) {
                foreach (include $file as $k => $v) {
                    $result[$k] = $v;
                }
            }
        }
        return $result;
    }

    /**
     * @template T
     * @param string $name
     * @param class-string<T> $class
     * @param bool $cache
     * @return T
     * @throws Throwable
     */
    protected function obj(string $name, string $class, bool $search_by_envs = true): object
    {
        $key = "$class:$name";
        if (!isset(self::$conf[$key])) {
            if (is_subclass_of($class, ToObject::class)) {
                try {
                    if ($search_by_envs && isset($_SERVER[$name])) {
                        $data = json_decode($_SERVER[$name], true);
                        if (!is_array($data)) {
                            Log::warn(
                                "Invalid loading config from envs by name: $name",
                                [
                                    'name'                => $name,
                                    'json_last_error_msg' => json_last_error_msg()
                                ],
                                "ap:conf"
                            );
                            $data = $this->array_by_php_file($name);
                        }
                    } else {
                        $data = $this->array_by_php_file($name);
                    }

                    self::$conf[$key] = $class::toObject($data);

                    if (self::$conf[$key] instanceof Validation) {
                        $res = self::$conf[$key]->isValid();
                        if ($res instanceof Errors) {
                            throw $res->getNodeErrorsThrowable();
                        }
                    }
                } catch (ThrowableErrors $e) {
                    throw new RuntimeException(
                        "Config `$name` must be JSON. Errors: " . self::renderErrors($e->getErrors()),
                        previous: $e
                    );
                }
            } else {
                throw new RuntimeException(
                    "The environment class `$class` must implement `AP\Scheme\ToObject` and may optionally implement `AP\Scheme\Validation`"
                );
            }
        }
        return self::$conf[$key];
    }

    private static function renderErrors(array $errors): string
    {
        $all = [];
        foreach ($errors as $error) {
            $all[] = implode(".", $error->path) . ": " . $error->getFinalMessage();
        }

        return implode("; ", $all);
    }
}