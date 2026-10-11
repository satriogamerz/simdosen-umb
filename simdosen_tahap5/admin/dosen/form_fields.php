
<?php
function select_ref($conn, $label, $field, $table, $idcol, $namecol, $selected = '') {
    echo '<div class="field"><label>' . htmlspecialchars($label) . '</label>';
    echo '<select name="' . htmlspecialchars($field) . '">';
    echo '<option value="">-- Pilih --</option>';

    $sql = "SELECT `$idcol`, `$namecol` FROM `$table` ORDER BY `$namecol`";
    $rs = $conn->query($sql);

    if ($rs) {
        while ($r = $rs->fetch_assoc()) {
            $val = (string)$r[$idcol];
            $sel = ($val === (string)$selected) ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($val) . '"' . $sel . '>';
            echo htmlspecialchars($r[$namecol]);
            echo '</option>';
        }
    }

    echo '</select></div>';
}
?>

<style>
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.field label{display:block;font-weight:bold;margin-bottom:5px}
.field input,.field select{width:100%;box-sizing:border-box;padding:10px}
.wide{grid-column:1/-1}
.btn{display:inline-block;padding:10px 14px;background:#1769aa;color:white;border:0;border-radius:7px;text-decoration:none;cursor:pointer}
@media(max-width:650px){.grid{grid-template-columns:1fr}.wide{grid-column:auto}}
</style>

<div class="grid">
<div class="field"><label>Nomor Registrasi</label><input required name="nomor_registrasi" value="<?=htmlspecialchars($d['nomor_registrasi']??'')?>"></div>
<div class="field"><label>NUPTK</label><input name="nuptk" value="<?=htmlspecialchars($d['nuptk']??'')?>"></div>
<div class="field"><label>Nama</label><input required name="nama" value="<?=htmlspecialchars($d['nama']??'')?>"></div>
<div class="field"><label>NIP</label><input name="nip" value="<?=htmlspecialchars($d['nip']??'')?>"></div>

<?php
select_ref($conn,'Jabatan Fungsional','id_jabatan','jabatan_fungsional','id_jabatan','nama_jabatan',$d['id_jabatan']??'');
select_ref($conn,'Pendidikan Terakhir','id_pendidikan','pendidikan','id_pendidikan','jenjang',$d['id_pendidikan']??'');
select_ref($conn,'Rumpun Ilmu','id_rumpun','rumpun_ilmu','id_rumpun','nama_rumpun',$d['id_rumpun']??'');
select_ref($conn,'Status Serdos','id_status_serdos','status_serdos','id_status_serdos','nama_status',$d['id_status_serdos']??'');
select_ref($conn,'Status Pegawai','id_status_pegawai','status_pegawai','id_status_pegawai','nama_status',$d['id_status_pegawai']??'');
select_ref($conn,'Ikatan Kerja','id_ikatan','ikatan_kerja','id_ikatan','nama_ikatan',$d['id_ikatan']??'');
select_ref($conn,'Status Aktivitas','id_status_aktivitas','status_aktivitas','id_status_aktivitas','nama_status',$d['id_status_aktivitas']??'');
select_ref($conn,'Penempatan','id_penempatan','penempatan','id_penempatan','nama_penempatan',$d['id_penempatan']??'');
select_ref($conn,'Program Studi','id_prodi','program_studi','id_prodi','nama_prodi',$d['id_prodi']??'');
?>

<div class="wide">
<button type="submit" class="btn">Simpan</button>
<a class="btn" href="index.php">Batal</a>
</div>
</div>