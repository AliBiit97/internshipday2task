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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .cards-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .box { 
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        .box h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.4em;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }

        input[type="text"], select {
            width: 100%;
            padding: 12px 15px;
            margin: 10px 0;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }

        label {
            color: #555;
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }

        .btn { 
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            margin-top: 15px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-danger { 
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 6px 12px;
            font-size: 12px;
            width: auto;
            margin: 0;
            display: inline-block;
        }

        .btn-danger:hover {
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
        }

        .table-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow-x: auto;
        }

        h2 { 
            color: white;
            margin-bottom: 20px;
            font-size: 2em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        table { 
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        td { 
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f9ff;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .sub-attribute-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0f0f0;
            padding: 6px 12px;
            border-radius: 20px;
            margin: 4px;
            font-size: 13px;
        }

        .attribute-name {
            color: #667eea;
            font-weight: 600;
            font-size: 16px;
        }

        .empty-state {
            text-align: center;
            color: #999;
            padding: 20px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 1.8em;
            }

            .cards-row {
                grid-template-columns: 1fr;
            }

            .table-container {
                padding: 15px;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🎨 Attribute & Sub-Attribute Manager</h1>

    <div class="cards-row">
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
                <label>Select Attribute:</label>
                <select name="attribute_id" required>
                    <?php
                    $atts = mysqli_query($conn, "SELECT * FROM attributes");
                    while ($row = mysqli_fetch_assoc($atts)) {
                        echo "<option value='{$row['id']}'>{$row['name']}</option>";
                    }
                    ?>
                </select>
                <input type="text" name="sub_value" placeholder="e.g. Blue, Small" required>
                <button class="btn" name="add_sub_attribute">Add Sub-Attribute</button>
            </form>
        </div>
    </div>

    <h2>📋 All Attributes & Sub-Attributes</h2>

    <div class="table-container">
        <table>
            <tr>
                <th>Attribute</th>
                <th>Sub-Attributes</th>
                <th style="text-align: center;">Actions</th>
            </tr>

            <?php
            $attributes = mysqli_query($conn, "SELECT * FROM attributes");

            while ($row = mysqli_fetch_assoc($attributes)) {
                $attr_id = $row['id'];

                echo "<tr>";
                echo "<td><span class='attribute-name'>{$row['name']}</span></td>";

                $values = mysqli_query($conn, "SELECT * FROM attribute_values WHERE attribute_id=$attr_id");

                echo "<td>";
                $has_values = false;
                while ($v = mysqli_fetch_assoc($values)) {
                    $has_values = true;
                    echo "<span class='sub-attribute-item'>{$v['value']} 
                          <a class='btn btn-danger' href='?delete_sub_attr={$v['id']}'>×</a></span>";
                }
                if (!$has_values) {
                    echo "<span class='empty-state'>No sub-attributes yet</span>";
                }
                echo "</td>";

                echo "<td style='text-align: center;'>
                        <a href='?delete_attribute={$attr_id}' class='btn btn-danger' onclick='return confirm(\"Delete this attribute and all its values?\")'>Delete Attribute</a>
                      </td>";

                echo "</tr>";
            }
            ?>
        </table>
    </div>
</div>

</body>
</html>