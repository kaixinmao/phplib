<?php
/**
 * 基础业务类
 * 相对于模型，是对具体业务流程的封装
 * 一个基本调用流程可以是
 * 数据提交 => 数据收集(文件上传，表单，或者接口) => 数据整理
 * => service流程处理接口 => 数据库操作 => 完成整个流程
 */
class Service_Base
{
    public function __construct()
    {
    }
}
