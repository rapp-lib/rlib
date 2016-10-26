R\Lib\Core\FileStorage
=====================================

Classes
-------------------------------------
    FileStorageManager
    FileStorage
    StoredFile
    R\Lib\Core\Util\File

Overview
-------------------------------------
    file_storage() => FileStorageManager::getInstance()
    file_storage()->create($storage_name, $file, $meta) => $stored_file_code
    file_storage()->create($storage_name) => $stored_file_code
    $stored_file->getCode() => $asset_code
    file_storage()->get($asset_code) => $stored_file
    $stored_file->getFile() => $file
    $stored_file->getUrl() => $url
    $stored_file->getMeta() => $meta
    $stored_file->remove()
    $stored_file->updateMeta($meta)
    $stored_file->isAccessible($use_case=null) => $is_accessible

StoredFile
-------------------------------------
    設定
        accessibility.web=true
            仮想的なURLからダウンロードを可能にする属性
            ※Storageでの指定があれば優先
                /storage:/tmp:/2016/10/20/xxxxxxxxx.png
        accessibility.private=$sess_id
            指定されたSessionIDを持たなければ一切のアクセスを許可しない
            ※Storageでの指定があれば優先
        accessibility.role=$role_name
            指定されたRoleでログインしていなければ一切のアクセスを許可しない
            ※Storageでの指定があれば優先

R\Lib\Core\Util\File
-------------------------------------
    util("File")->write($file, $content)
    util("File")->read($file)
    util("File")->move($file_from, $file_to)
    util("File")->copy($file_from, $file_to)
    util("File")->remove($file)
