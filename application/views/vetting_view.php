<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Result Processing System</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 13px; margin: 20px; background-color: #ffffff; color: #000000;">

    <h2>Faculty Result Processing System</h2>
    <!-- <p>Direct PDF Ingestion & Extraction Module (Non-Calculating Prototype)</p> -->
    <!-- <hr style="border: 1px solid #ccc; margin-bottom: 15px;"> -->

    <!-- Flash Message -->
    <?php if ($this->session->flashdata('msg')): ?>
        <div style="background-color: #eef9ff; border: 1px solid #bce8f1; padding: 10px; margin-bottom: 15px; color: #31708f;">
            <b>System Notice:</b> <?= htmlspecialchars($this->session->flashdata('msg')) ?>
        </div>
    <?php endif; ?>

    <!-- Upload Form -->
    <fieldset style="border: 1px solid #999; padding: 12px; margin-bottom: 15px; background: #f9f9f9;">
        <legend><b>Upload Master Sheet PDF</b></legend>
        <form action="<?= site_url('vetting/upload_pdf') ?>" method="post" enctype="multipart/form-data">
            Select PDF File: 
            <input type="file" name="pdf_file" accept=".pdf" required style="font-size: 12px;">
            <button type="submit" style="padding: 4px 12px; font-size: 12px; font-weight: bold;">Parse into Table</button>
            <a href="<?= site_url('vetting/clear') ?>" onclick="return confirm('Clear table?');" style="margin-left: 20px; color: red; font-size: 12px;">Clear Table</a>
        </form>
    </fieldset>

    <!-- Search Box -->
    <div style="margin-bottom: 10px;">
        Search Student: <input type="text" id="searchBox" onkeyup="filterTable()" placeholder="Type matric or name..." style="padding: 3px; width: 220px;">
        <span style="float: right; color: #555;">
            Total Extracted Records: <b><?= count($students) ?></b>
        </span>
    </div>

    <!-- Tabular View -->
    <table border="1" cellspacing="0" cellpadding="6" width="100%" id="studentTable" style="border-collapse: collapse; border-color: #999; font-size: 12px;">
        <thead style="background-color: #e0e0e0;">
            <tr>
                <th width="5%">S/N</th>
                <th width="20%">Matric Number</th>
                <th width="30%">Student Name</th>
                <th width="10%">TUR</th>
                <th width="35%">Extracted Record Stream Preview</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="5" align="center" style="padding: 25px; color: #666;">
                        No PDF data loaded yet. Upload a master sheet above to display extracted student records in this table.
                    </td>
                </tr>
            <?php else: ?>
                <?php $sn = 1; foreach ($students as $row): ?>
                    <tr>
                        <td align="center"><?= $sn++ ?></td>
                        <td><b><?= htmlspecialchars($row['matric_no']) ?></b></td>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td align="center"><?= htmlspecialchars($row['tur']) ?></td>
                        <td style="font-family: monospace; color: #444;"><?= htmlspecialchars($row['extra_info']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Search Filter Script -->
    <script>
    function filterTable() {
        var input, filter, table, tr, td1, td2, i;
        input = document.getElementById("searchBox");
        filter = input.value.toUpperCase();
        table = document.getElementById("studentTable");
        tr = table.getElementsByTagName("tr");
        for (i = 1; i < tr.length; i++) {
            td1 = tr[i].getElementsByTagName("td")[1];
            td2 = tr[i].getElementsByTagName("td")[2];
            if (td1 || td2) {
                var t1 = td1.textContent || td1.innerText;
                var t2 = td2.textContent || td2.innerText;
                if (t1.toUpperCase().indexOf(filter) > -1 || t2.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
    </script>

</body>
</html>