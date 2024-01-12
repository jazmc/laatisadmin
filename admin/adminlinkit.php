<div class="d-flex btn-group-toggle justify-content-center mt-3 mb-5" style="width:100%;" data-toggle="buttons">
    <label class="btn btn-secondary mx-2<?php echo (basename($_SERVER['REQUEST_URI']) == 'index.php' ? " active font-weight-bold" : ""); ?>" onclick="location.href = 'index.php'">
        <input type="radio" name="options" id="option1"> Osioiden hallinta
    </label>
    <label class="btn btn-secondary mx-2<?php echo (basename($_SERVER['REQUEST_URI']) == 'tilaisuuksienhallinta.php' ? " active font-weight-bold" : ""); ?>" onclick="location.href = 'tilaisuuksienhallinta.php'">
        <input type="radio" name="options" id="option2"> Tilaisuuksien hallinta
    </label>
    <label class="btn btn-secondary mx-2<?php echo (basename($_SERVER['REQUEST_URI']) == 'tuomarit.php' ? " active font-weight-bold" : ""); ?>" onclick="location.href = 'tuomarit.php'">
        <input type="radio" name="options" id="option3"> Tuomareiden hallinta
    </label>
</div>