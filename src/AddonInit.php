<?php

    namespace yicmf\addon;

    use app\common\model\Hook as HookModel;
    use app\common\model\Addon as AddonModel;
    use think\facade\Env;
    use think\facade\Cache;
    use think\Loader;
    use think\facade\Hook;
    use think\facade\Route;

    class AddonInit
    {

        /**
         * 入口文件
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/4/27 21:29
         */
        public function run()
        {
            // 定义路由
            Route::rule('addon/execute/:_addon/:_controller/:_action', 'yicmf\addon\controller\Index@index');
            // 插件目录
            Env::set('addon_path', Env::get('root_path') . 'addon' . DIRECTORY_SEPARATOR);
            // 如果插件目录不存在则创建
            if (!is_dir(Env::get('addon_path'))) {
                mkdir(Env::get('addon_path'), 0777, true);
            }
            if ('cli' != PHP_SAPI) {
                if ($_SERVER['DOCUMENT_ROOT'] && (strlen($_SERVER['DOCUMENT_ROOT']) - 7) == strpos($_SERVER['DOCUMENT_ROOT'], '/public')) {
                    //SCRIPT_FILENAME
                    Env::set('addon_static', Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'addon' . DIRECTORY_SEPARATOR);
                } else {
                    Env::set('addon_static', Env::get('root_path') . 'addon' . DIRECTORY_SEPARATOR);
                }
            } else {
                Env::set('addon_static', Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'addon' . DIRECTORY_SEPARATOR);
            }
            // 注册类的根命名空间
            Loader::addNamespace('addon', Env::get('addon_path'));
            // 获取系统配置
            $addon_hooks = !Cache::has('addon_hooks') ? [] : Cache::get('addon_hooks');
            if (empty($addon_hooks)) {
                $hooks = HookModel::order('id')->field('addons,name')->select();
                foreach ($hooks as $hook) {
                    if (!empty($hook['addons'])) {
                        $addons = AddonModel::where('status', 1)
                            ->where('name', 'in', $hook['addons'])
                            ->column('name');
                        if ($addons) {
                            $addon_hooks[$hook['name']] = array_map('get_addon_class', $addons);
                            Hook::add($hook['name'], $addon_hooks[$hook['name']]);
                        }
                    }
                }
                Cache::set('addon_hooks', $addon_hooks, 3600);
            } else {
                Hook::import($addon_hooks, false);
            }
        }
    }

