<?php
use Doctrine\DBAL\DriverManager;

$conn = DriverManager::getConnection($params, $config);

$sql = "SELECT * FROM articles";
$stmt = $conn->query($sql); // Simple, but has several drawbacks

while ($row = $stmt->fetch()) {
    echo $row['headline'];
}
// quoteIdentifier