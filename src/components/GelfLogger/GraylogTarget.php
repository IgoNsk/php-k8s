<?php

namespace app\components\GelfLogger;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\log\Target;
use yii\log\Logger;
use Gelf;
use Psr\Log\LogLevel;

/**
 * GraylogTarget sends log to Graylog2 (in GELF format)
 *
 */
class GraylogTarget extends Target
{
    /**
     * @var string Type
     */
    public $facility = 'application';

    /**
     * @var string хост
     */
    public $host;

    /**
     * @var int порт
     */
    public $port;

    /**
     * @var string дополнительный набор тегов для правильной обработки logstash
     */
    public $tags;

    /**
     * @var string префикс индекса для правильной обработки logstash
     */
    public $logstashPrefix;

    /**
     * @var string название индекса, добавляемое после префикса. Лучше всего использовать название приложения.
     */
    public $namespace;

    /**
     * Дополнительный контекст к каждому из сообщений
     * @var array
     */
    public $additionalContext = [];

    /**
     * @var Callable[]
     */
    public $logVarsConverters = [];

    /**
     * @var array graylog levels
     */
    private $levelsMap = [
        Logger::LEVEL_TRACE => LogLevel::DEBUG,
        Logger::LEVEL_PROFILE_BEGIN => LogLevel::DEBUG,
        Logger::LEVEL_PROFILE_END => LogLevel::DEBUG,
        Logger::LEVEL_INFO => LogLevel::INFO,
        Logger::LEVEL_WARNING => LogLevel::WARNING,
        Logger::LEVEL_ERROR => LogLevel::ERROR,
    ];

    private $_levels = 0;

    /**
     * @inheritDoc
     */
    public function setLevels($levels)
    {
        static $levelMap = [
            'error'   => Logger::LEVEL_ERROR,
            'warning' => Logger::LEVEL_WARNING,
            'info'    => Logger::LEVEL_INFO,
            'trace'   => Logger::LEVEL_TRACE,
            'profile' => Logger::LEVEL_PROFILE,
        ];
        if (is_array($levels)) {
            $this->_levels = 0;
            foreach ($levels as $level) {
                if (isset($levelMap[$level])) {
                    $this->_levels |= $levelMap[$level];
                } else {
                    throw new InvalidConfigException("Unrecognized level: $level");
                }
            }
        } else {
            $this->_levels = $levels;
        }
    }

    /**
     * Добавляем конвертацию значений конкретных переменных
     *
     * @inheritdoc
     */
    protected function getContextMessage()
    {
        $context = ArrayHelper::filter($GLOBALS, $this->logVars);
        $result = [];
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (isset($this->logVarsConverters[$k]) && is_callable($this->logVarsConverters[$k])) {
                        $val = call_user_func($this->logVarsConverters[$k], $v);
                        $value[$k] = $val;
                    }
                }
            }

            $result[] = "\${$key} = " . VarDumper::dumpAsString($value);
        }
        return implode("\n\n", $result);
    }

    /**
     * Sends log messages to Graylog2 input
     */
    public function export()
    {
        $transport = new Gelf\Transport\UdpTransport($this->host, $this->port, Gelf\Transport\UdpTransport::CHUNK_SIZE_LAN);
        $publisher = new Gelf\Publisher($transport);
        foreach ($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;

            $gelfMsg = new Gelf\Message;
            $gelfMsg->setLevel(ArrayHelper::getValue($this->levelsMap, $level, LogLevel::INFO))
                ->setTimestamp($timestamp)
                ->setFacility($this->facility);

            $msgAdditional = ArrayHelper::merge(
                $this->parseText($text),
                [
                    'type'            => $this->facility,
                    'tags'            => $this->tags,
                    'logstash_prefix' => $this->logstashPrefix,
                    'namespace'       => $this->namespace,
                    'level'           => $level,
                    'log_level'       => isset($this->levelsMap[$level]) ? $this->levelsMap[$level] : 'unknown',
                    'category'        => $category,
                ],
                $this->getAdditionalContext()
            );

            // Set 'file', 'line' and additional 'trace', if log message contains traces array
            if (isset($message[4]) && is_array($message[4])) {
                $traces = [];
                foreach ($message[4] as $index => $trace) {
                    $traces[] = "{$trace['file']}:{$trace['line']}";
                    if ($index === 0) {
                        $gelfMsg->setFile($trace['file']);
                        $gelfMsg->setLine($trace['line']);
                    }
                }
                $gelfMsg->setAdditional('trace', implode("\n", $traces));
            }

            $msgAdditional = $this->prepareMessageToSend($msgAdditional);

            if ($text instanceof \Exception) {
                $gelfMsg->setShortMessage('Exception ' . get_class($text) . ': ' . $text->getMessage());
                $gelfMsg->setFullMessage((string)$text);
                $gelfMsg->setLine($text->getLine());
                $gelfMsg->setFile($text->getFile());
            } else if (isset($msgAdditional['message'])) {
                $gelfMsg->setShortMessage($msgAdditional['message']);
                $gelfMsg->setFullMessage($msgAdditional['message']);
            } else {
                $gelfMsg->setShortMessage("Unknown");
            }

            foreach ($msgAdditional as $key => $value) {
                $gelfMsg->setAdditional($key, $value);
            }

            $publisher->publish($gelfMsg);
        }
    }

    /**
     * Для корректного заполнения сообщения перед отправкой необходимо привести все значения полей
     *  сообщения к скалярному виду, иначе получим Warning при попытке преобразовать сообщение в массив.
     * @see \Gelf\Message::toArray():303
     *
     * @param $message
     *
     * @return mixed
     */
    private function prepareMessageToSend($message)
    {
        foreach ($message as $key => $val) {
            if (is_array($val) || is_object($val)) {
                $message[$key] = json_encode($val);
            }
        }
        return $message;
    }

    /**
     * Делает попытку распарсить текст сообщения
     *
     * @return array
     */
    protected function parseText($text)
    {
        $type = gettype($text);
        switch ($type) {
            case 'array':
                return $text;
            case 'string':
                return ['message' => $text];
            case 'object':
                if ($text instanceof \Throwable || $text instanceof \Exception) {
                    return ['message' => (string)$text];
                }
                return get_object_vars($text);
            default:
                return ['message' => \Yii::t('log', "Warning, wrong log message type '{$type}'")];
        }
    }

    /**
     * Формирует и возвращает дополнительный контекст, прикрепляемый к сообщению, в виде массива
     *
     * @return array
     */
    protected function getAdditionalContext()
    {
        $context = [];
        foreach ($this->additionalContext as $key => $value) {
            if (is_callable($value)) {
                $context[$key] = call_user_func($value, $key);
                continue;
            }

            $context[$key] = $value;
        }

        return $context;
    }

    /**
     * @inheritdoc
     */
    public function getLevels()
    {
        return $this->_levels;
    }
}
