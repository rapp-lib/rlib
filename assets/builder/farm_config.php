<?php
    return $config = array(
        "app_root_dir" => constant("R_APP_ROOT_DIR"),
        "work_root_dir" => constant("R_APP_ROOT_DIR")."/tmp/farm/work",
        "farm_dirname" => "devel/builder",
        "develop_branch" => false,
        "farm_branch" => "farm/build",
        "farm_mark" => array("-m", "<FARM>"),
        "farm_mark_find" => array("--grep=", "<FARM>"),
        "root_mark" => array("-m", "<FARM><INIT>"),
        "root_find" => array("--grep=", "<FARM><INIT>"),
        "build_callback" => function($farm){
            $farm_dir = $farm->getConfig("app_root_dir")."/".$farm->getConfig("farm_dirname");
            with($builder = new \R\Lib\Builder\WebappBuilder())->build(array(
                "skel_dir"=>$farm_dir."/skel",
                "schema_csv_file"=>$farm_dir."/schema.config.csv",
                "deploy_callback"=>function($deploy_name, $source, $config_entry, $vars) use ($farm){
                    return call_user_func($farm->getConfig("deploy_callback"),
                        $deploy_name, $source, $config_entry, $vars, $farm);
                }
            ));
        },
        "deploy_callback"=>function($deploy_name, $source, $config_entry, $vars, $farm){
            $deploy_name = preg_replace('!^/!', '', $deploy_name);
            $deploy_file = $farm->getConfig("work_root_dir")."/".$deploy_name;
            $status = "create";
            if (file_exists($deploy_file)) {
                $current_source = $farm->cmdWork(array("git", "cat-file", "-p",
                    $farm->getConfig("farm_branch").":".$deploy_name),
                    array("quiet"=>true, "return"=>"rawoutput"));
                $status = crc32($current_source)==crc32($source) ? "nochange" : "modify";
            }
            if ( ! $farm->getConfig("option.dryrun")) {
                \R\Lib\Util\File::write($deploy_file, $source);
            }
            if ($status != "nochange") {
                print "[PUBLISH] ".$status." ".$deploy_name."\n";
            }
        },
    );
