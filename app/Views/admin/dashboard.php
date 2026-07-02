<div class="container-fluid">
  <div class="row">
    <div class="col-lg-3 col-6">
      <!-- small box -->
      <div class="small-box bg-info">
        <div class="inner">
          <h3><?php echo $kk; ?></h3>

          <p>KK</p>
        </div>
        <div class="icon">
          <i class="fas fa-file-alt"></i>
        </div>
        <a href="<?php echo base_url('admin/warga') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
      <!-- small box -->
      <div class="small-box bg-success">
        <div class="inner">
          <h3><?php echo $warga; ?></h3>

          <p>Warga</p>
        </div>
        <div class="icon">
          <i class="fas fa-users"></i>
        </div>
        <a href="<?php echo base_url('admin/warga') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
      <!-- small box -->
      <div class="small-box bg-warning">
        <div class="inner">
          <h3><?php echo $berita; ?></h3>
          <p>Berita</p>
        </div>
        <div class="icon">
          <i class="fas fa-book"></i>
        </div>
        <a href="<?php echo base_url('admin/berita') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
      <!-- small box -->
      <div class="small-box bg-danger">
        <div class="inner">
          <h3><?php echo $surat; ?></h3>
          <p>Layanan</p>
        </div>
        <div class="icon">
          <i class="fas fa-info"></i>
        </div>
        <a href="<?php echo base_url('admin/surat') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <!-- ./col -->
  </div>

  <div class="row">
    <div class="col-12 col-sm-6 col-md-3">
      <div class="info-box">
        <span class="info-box-icon bg-info elevation-1"><i class="fas fa-male"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Laki-Laki</span>
          <span class="info-box-number">
          <?php echo $laki; ?>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3">
      <div class="info-box mb-3">
        <span class="info-box-icon bg-success elevation-1"><i class="fas fa-female"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Perempuan</span>
          <span class="info-box-number"><?php echo $perempuan; ?></span>
        </div>
      </div>
    </div>


    <div class="clearfix hidden-md-up"></div>
    <div class="col-12 col-sm-6 col-md-3">
      <div class="info-box mb-3">
        <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-home"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Total Rumah</span>
          <span class="info-box-number"><?php echo $alamat; ?></span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3">
      <div class="info-box mb-3">
        <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-users"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Rumah Kosong</span>
          <span class="info-box-number"><?php echo $kosong; ?></span>
        </div>
      </div>
    </div>
  </div>
</div><!-- /.container-fluid -->