<?php

declare(strict_types=1);

namespace idwork;

class Idwork
{
    // 定义算法的各个参数
    private $workerIdBits = 5;
    private $datacenterIdBits = 5;
    private $sequenceBits = 12;

    private $workerIdShift;
    private $datacenterIdShift;
    private $timestampLeftShift;

    private $maxWorkerId;
    private $maxDatacenterId;
    private $sequenceMask;

    private $workerId;
    private $datacenterId;
    private $sequence = 0;
    private $lastTimestamp = -1;

    public function __construct($workerId = 1, $datacenterId = 1)
    {
        // 计算位偏移量
        $this->workerIdShift = $this->sequenceBits;
        $this->datacenterIdShift = $this->sequenceBits + $this->workerIdBits;
        $this->timestampLeftShift = $this->sequenceBits + $this->workerIdBits + $this->datacenterIdBits;
        // 计算最大ID
        $this->maxWorkerId = -1 ^ (-1 << $this->workerIdBits);
        $this->maxDatacenterId = -1 ^ (-1 << $this->datacenterIdBits);
        $this->sequenceMask = -1 ^ (-1 << $this->sequenceBits);
        // 初始化参数
        $this->workerId = $workerId;
        $this->datacenterId = $datacenterId;
    }

    // 生成下一个唯一ID
    public function generateId()
    {
        // 获取当前时间戳（毫秒级）
        $timestamp = floor(microtime(true) * 1000);
        // 如果当前时间小于上次生成ID的时间戳，则抛出异常
        if ($timestamp < $this->lastTimestamp) {
            throw new \Exception("Invalid system clock!");
        }
        // 如果当前时间戳与上次时间戳相同，则自增序列号
        if ($timestamp == $this->lastTimestamp) {
            $this->sequence = ($this->sequence + 1) & $this->sequenceMask;

            // 如果序列号等于0，则需要进入下一毫秒重新生成ID
            if ($this->sequence == 0) {
                $timestamp = $this->waitNextMillis($this->lastTimestamp);
            }
        } else {
            $this->sequence = 0;
        }
        // 保存最后生成ID的时间戳
        $this->lastTimestamp = $timestamp;
        // 生成最终的唯一ID
        $uniqueId = (($timestamp << $this->timestampLeftShift) |
            ($this->datacenterId << $this->datacenterIdShift) |
            ($this->workerId << $this->workerIdShift) |
            $this->sequence);
        return $uniqueId;
    }

    // 阻塞到下一个毫秒，直到获得新的时间戳
    private function waitNextMillis($lastTimestamp)
    {
        $timestamp = floor(microtime(true) * 1000);
        while ($timestamp <= $lastTimestamp) {
            usleep(1000);
            $timestamp = floor(microtime(true) * 1000);
        }
        return $timestamp;
    }
}
