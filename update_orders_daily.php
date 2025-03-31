<?php
require_once 'config.php';

// Update orders from previous days to "delivered" status if they aren't already
$update_orders_query = "UPDATE orders 
                       SET status = 'delivered' 
                       WHERE DATE(created_at) < CURDATE()
                       AND status IN ('processing', 'out_for_delivery', 'pending', 'confirmed')";

if ($conn->query($update_orders_query)) {
    echo "Orders updated successfully: " . $conn->affected_rows . " orders marked as delivered.\n";
} else {
    echo "Error updating orders: " . $conn->error . "\n";
}

$conn->close();
?>