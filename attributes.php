<?php
include("db.php"); 

if (isset($_POST['add_attribute'])) {
    $name = $_POST['attribute_name'];

    if (!empty($name)) {
        $sql = "INSERT INTO attributes (name) VALUES ('$name')";
        mysqli_query($conn, $sql);
    }
}

if (isset($_POST['add_sub_attribute'])) {
    $attribute_id = $_POST['attribute_id'];
    $value = $_POST['sub_value'];

    if (!empty($value)) {
        $sql = "INSERT INTO attribute_values (attribute_id, value) VALUES ('$attribute_id', '$value')";
        mysqli_query($conn, $sql);
    }
}

if (isset($_GET['delete_attribute'])) {
    $id = $_GET['delete_attribute'];
    mysqli_query($conn, "DELETE FROM attributes WHERE id=$id"); // CASCADE removes sub-values
}

if (isset($_GET['delete_sub_attr'])) {
    $id = $_GET['delete_sub_attr'];
    mysqli_query($conn, "DELETE FROM attribute_values WHERE id=$id");
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Attributes</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .box { width: 400px; padding: 20px; border: 1px solid #ddd; margin-bottom: 20px; border-radius: 6px; background: #fafafa; }
        .btn { padding: 8px 14px; background: #28a745; color: #fff; border: none; cursor: pointer; border-radius: 4px; }
        .btn-danger { background: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        h2 { margin-top: 40px; }
    </style>
</head>
<body>

<h1> Attribute & Sub-Attribute Manager</h1>

<div class="box">
    <h3>Add New Attribute</h3>
    <form method="POST">
        <input type="text" name="attribute_name" placeholder="e.g. Color, Size" required>
        <button class="btn" name="add_attribute">Add Attribute</button>
    </form>
</div>


<div class="box">
    <h3>Add Sub-Attribute (Value)</h3>
    <form method="POST">
        <label>Select Attribute:</label><br>
        <select name="attribute_id" required>
            <?php
            $atts = mysqli_query($conn, "SELECT * FROM attributes");
            while ($row = mysqli_fetch_assoc($atts)) {
                echo "<option value='{$row['id']}'>{$row['name']}</option>";
            }
            ?>
        </select>
        <br><br>
        <input type="text" name="sub_value" placeholder="e.g. Blue, Small" required>
        <button class="btn" name="add_sub_attribute">Add Sub-Attribute</button>
    </form>
</div>

<h2>All Attributes & Sub-Attributes</h2>

<table>
    <tr>
        <th>Attribute</th>
        <th>Sub-Attributes</th>
        <th>Actions</th>
    </tr>

    <?php
    $attributes = mysqli_query($conn, "SELECT * FROM attributes");

    while ($row = mysqli_fetch_assoc($attributes)) {
        $attr_id = $row['id'];

        echo "<tr>";
        echo "<td><b>{$row['name']}</b></td>";

        $values = mysqli_query($conn, "SELECT * FROM attribute_values WHERE attribute_id=$attr_id");

        echo "<td>";
        while ($v = mysqli_fetch_assoc($values)) {
            echo "{$v['value']} 
                  <a class='btn-danger' href='?delete_sub_attr={$v['id']}' style='padding:3px 5px;'>x</a> 
                  <br>";
        }
        echo "</td>";

        echo "<td>
                <a href='?delete_attribute={$attr_id}' class='btn btn-danger'>Delete Attribute</a>
              </td>";

        echo "</tr>";
    }
    ?>
</table>
</body>
</html>
