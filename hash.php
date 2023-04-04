<?php

declare(strict_types=1);

/**
 * データ抽出結果の特定カラムをハッシュ化するツール
 *
 * ・第一引数のハッシュキーについては知っている人を探して入手してください。
 * ・第二引数はカンマ区切りで複数指定可能です。
 * ・Mysqlの出力ファイル（＝タブ区切りファイル）を想定しています。
 * ・入力・出力ともにカレントディレクトリで行います。
 * ・結果は固定で"hashed_" + 日時のファイル名で出力します。
 */

error_reporting(0);

echo "Usage: php -f hash.php {hash key} {target columns} {input filename}\n";
echo "Started.\n";

function hashedId(string $id, string $key): string
{
    if ($key === '') {
        die("Hash key is required.\n");
    }
    return hash('sha256',$id . $key);
}

$separator = "\t";
$targetColumns = explode(',', $argv[2] ?? '');
$hashKey = $argv[1] ?? '';

$currentDirectory = getcwd();
$filePath = "$currentDirectory/$argv[3]";
$inputStream = fopen($filePath, 'r');
if (!$inputStream) {
    echo "Cannot read file.\n";
    return 1;
}
$header = fgetcsv(
    stream: $inputStream,
    separator: $separator,
);
if (empty($header)) {
    echo "No data.\n";
    return 1;
}
$now = date('YmdHis');
$outputStream = fopen("$currentDirectory/hashed_$now.txt", 'w');
$written = fputcsv(
    stream: $outputStream,
    fields: $header,
    separator: $separator,
);
if ($written === false) {
    echo "Cannot write file.\n";
    return 0;
}
while (true) {
    $row = fgetcsv(
        stream: $inputStream,
        separator: $separator,
    );
    if ($row === false) {
        break;
    }
    $fields = array_combine($header, $row);
    foreach ($targetColumns as $targetColumn) {
        if (isset($fields[$targetColumn])) {
            $fields[$targetColumn] = hashedId($fields[$targetColumn], $hashKey);
        }
    }

    $written = fputcsv(
        stream: $outputStream,
        fields: $fields,
        separator: $separator,
    );
    if ($written === false) {
        echo "Cannot write file.\n";
        return 0;
    }
}

echo "Done.\n";
return 0;
