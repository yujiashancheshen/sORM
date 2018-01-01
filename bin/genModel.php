<?php
ini_set('display_errors', 1);
error_reporting(E_ERROR | E_WARNING);

require_once __DIR__ . '/../config.php';

$modelDir = __DIR__ . '/../model/';

if (!is_dir($modelDir)) {
    mkdir($modelDir, 755);
}

foreach ($TABLES as $tableName => $dbName) {
    
    $dbconfig = [
        'dsn' => "mysql:dbname={$dbName};host={$DBS[$dbName]['host']}",
        'user' => $DBS[$dbName]['user'],
        'password' => $DBS[$dbName]['password'],
    ];
    
    $tableFields = getFields($dbconfig, $tableName);

    genFile($tableName, $tableFields);
}

function genFile($tableName, $tableFields) {
    $className = genClassName($tableName);
    global $modelDir;
    $fileName = $modelDir . $className . '.php';
    @unlink($fileName);

    $content = "<?php\n";
    $content .= "require_once __DIR__ . \"/../orm/model.php\";\n\n";
    $content .= "class {$className} extends Model {\n\n";
    $content .= "    public static \$table = '{$tableName}';\n\n";

    $fieldsMap = "    public static \$fields = [\n";
    foreach ($tableFields as $field) {
       
        if ($field['Key'] == 'PRI') {
            $content .= "    public static \$primaryKey = '" . $field['Field'] . "';\n\n";
        }

        $fieldsMap .= "        '" . $field['Field'] . "' => '" . genFieldName($field['Field']) . "',\n";
    }
    $fieldsMap .= "    ];\n";

    $content .= $fieldsMap;
    $content .= "}\n";
    
    file_put_contents($fileName, $content, FILE_APPEND);
}

function genClassName($tableName) {
    return implode(array_map('ucfirst', explode('_', $tableName))) . 'Model';
}

function genFieldName($fieldName) {
    $fieldNames = explode('_', $fieldName);
    $first = array_shift($fieldNames);
    
    return $first . implode(array_map('ucfirst', $fieldNames));
}

function getFields($dbconfig, $tableName) {
    $pdo = new PDO($dbconfig['dsn'], $dbconfig['user'], $dbconfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SHOW COLUMNS FROM ' . $tableName);  
    $stmt->execute();  
    $tableFields = $stmt->fetchAll(PDO::FETCH_ASSOC); 

    return $tableFields;
}
