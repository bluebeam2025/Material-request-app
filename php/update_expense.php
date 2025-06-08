for ($i = 0; $i < count($ids); $i++) {
    $eid = (int)$ids[$i];
    $d = trim($desc[$i]);
    $c = trim($cat[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $r = trim($remarks[$i]);

    if ($eid > 0) {
        // Update existing
        $update->bind_param("ssddssi", $d, $c, $in, $out, $r, $eid, $request_id);
        $update->execute();
    } else if ($d || $c || $in > 0 || $out > 0 || $r) {
        // Insert new if any field is filled
        $insert = $conn->prepare("
            INSERT INTO expense_entries (request_id, description, category, cash_in, cash_out, remarks)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insert->bind_param("issdds", $request_id, $d, $c, $in, $out, $r);
        $insert->execute();
    }
}
