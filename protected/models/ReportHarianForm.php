<?php

/**
 * ReportHarianForm class.
 * ReportHarianForm is the data structure for keeping
 * report harian form data. It is used by the 'harian' action of 'ReportController'.
 * 
 * The followings are the available model relations:
 * @property Profil $profil
 */
class ReportHarianForm extends CFormModel {

   public $tanggal;

   /**
    * Declares the validation rules.
    */
   public function rules() {
      return array(
          array('tanggal', 'required', 'message' => '{attribute} tidak boleh kosong'),
      );
   }

   /**
    * Declares attribute labels.
    */
   public function attributeLabels() {
      return array(
          'tanggal' => 'Tanggal'
      );
   }

   /**
    * Report Harian
    * @return array Nilai-nilai yang diperlukan untuk report harian
    */
   public function reportHarian() {
      $date = isset($this->tanggal) ? date_format(date_create_from_format('d-m-Y', $this->tanggal), 'Y-m-d') : NULL;
      $laporanHarian = LaporanHarian::model()->find('tanggal=:tanggal', array(':tanggal' => $date));
      if (is_null($laporanHarian)) {
         /* Object, tidak untuk disimpan, hanya untuk mencari nilai per tanggal */
         $laporanHarian = new LaporanHarian;
         $laporanHarian->tanggal = date_format(date_create_from_format('d-m-Y', $this->tanggal), 'Y-m-d');
      } else {
         /* fixme: ganti afterFind() */
         $laporanHarian->tanggal = date_format(date_create_from_format('d-m-Y', $laporanHarian->tanggal), 'Y-m-d');
      }
      return array(
          'saldoAwal' => $laporanHarian->saldoAwal(),
          'saldoAkhir' => $laporanHarian->saldoAkhir(),
          'saldoAkhirAsli' => $laporanHarian->saldo_akhir,
          /* ========================================================== */
          'penjualanTunai' => $laporanHarian->penjualanTunai(),
          'totalPenjualanTunai' => $laporanHarian->totalPenjualanTunai(),
          'penjualanPiutang' => $laporanHarian->penjualanPiutang(),
          'totalPenjualanPiutang' => $laporanHarian->totalPenjualanPiutang(),
          'penjualanBayar' => $laporanHarian->penjualanBayar(),
          'totalPenjualanBayar' => $laporanHarian->totalPenjualanBayar(),
          /* ========================================================== */
          'margin' => $laporanHarian->marginPenjualanTunai(),
          'totalMargin' => $laporanHarian->totalMarginPenjualanTunai(),
          /* ========================================================== */
          'pembelianTunai' => $laporanHarian->pembelianTunai(),
          'totalPembelianTunai' => $laporanHarian->totalPembelianTunai(),
          'pembelianHutang' => $laporanHarian->pembelianHutang(),
          'totalPembelianHutang' => $laporanHarian->totalPembelianHutang(),
          'pembelianBayar' => $laporanHarian->pembelianBayar(),
          'totalPembelianBayar' => $laporanHarian->totalPembelianBayar(),
          /* ========================================================== */
          'itemPengeluaran' => $laporanHarian->itemPengeluaran(),
          'itemPenerimaan' => $laporanHarian->itemPenerimaan(),
          /* ========================================================== */
          'returBeliTunai' => $laporanHarian->returBeliTunai(),
          'totalReturBeliTunai' => $laporanHarian->totalReturBeliTunai(),
          'returBeliPiutang' => $laporanHarian->returBeliPiutang(),
          'totalReturBeliPiutang' => $laporanHarian->totalReturBeliPiutang(),
          'returBeliBayar' => $laporanHarian->returBeliBayar(),
          'totalReturBeliBayar' => $laporanHarian->totalReturBeliBayar(),
          /* ========================================================== */
          'returJualTunai' => $laporanHarian->returJualTunai(),
          'totalReturJualTunai' => $laporanHarian->totalReturJualTunai(),
          'returJualHutang' => $laporanHarian->returJualHutang(),
          'totalReturJualHutang' => $laporanHarian->totalReturJualHutang(),
          'returJualBayar' => $laporanHarian->returJualBayar(),
          'totalReturJualBayar' => $laporanHarian->totalReturJualBayar(),
      );
   }

   /**
    * Pembelian yang dibayar di hari yang sama
    * @param date $tanggal
    * @return array Pembelian tunai per trx (nomor pembelian, profil, total)
    */
   private function _pembelianTunai($tanggal) {
      $command = Yii::app()->db->createCommand("
         select nomor, sum(jumlah) jumlah, profil.nama
         FROM
         (
            select pembelian.id, d.jumlah
            from penerimaan_detail d
            join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join pembelian on hp.id = pembelian.hutang_piutang_id and date_format(pembelian.tanggal,'%Y-%m-%d')=:tanggal
            union
            select pembelian.id, d.jumlah
            from pengeluaran_detail d
            join pengeluaran p on d.pengeluaran_id = p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join pembelian on hp.id = pembelian.hutang_piutang_id and date_format(pembelian.tanggal,'%Y-%m-%d')=:tanggal
         ) t 
         join pembelian on t.id = pembelian.id
         join profil on pembelian.profil_id = profil.id
         group by t.id");


      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PEMBELIAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      return $command->queryAll();
   }

   private function _totalPembelianTunai($tanggal) {
      $command = Yii::app()->db->createCommand("
        select sum(jumlah) total
         FROM
         (
            select pembelian.id, d.jumlah
            from penerimaan_detail d
            join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join pembelian on hp.id = pembelian.hutang_piutang_id and date_format(pembelian.tanggal,'%Y-%m-%d')=:tanggal
            union
            select pembelian.id, d.jumlah
            from pengeluaran_detail d
            join pengeluaran p on d.pengeluaran_id = p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join pembelian on hp.id = pembelian.hutang_piutang_id and date_format(pembelian.tanggal,'%Y-%m-%d')=:tanggal
         ) t");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PEMBELIAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      $pembelian = $command->queryRow();
      return $pembelian['total'];
   }

   /**
    * Pembelian yang masih hutang
    * @param date $tanggal
    * @return array Pembelian pada tanggal tsb yang masih hutang per trx (nomor pembelian, profil, total)
    */
   private function _pembelianHutang($tanggal) {
      $command = Yii::app()->db->createCommand("
         select pembelian.nomor, profil.nama, t3.jumlah-t3.jml_bayar jumlah
         from
         (
            select pb.id, hp.jumlah, sum(ifnull(t1.jumlah,0)+ifnull(t2.jumlah,0)) jml_bayar 
            from pembelian pb
            join hutang_piutang hp on pb.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            left join
            (
               select pd.* from penerimaan_detail pd
               join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            ) t1 on hp.id=t1.hutang_piutang_id
            left join
            (
               select pd.* from pengeluaran_detail pd
               join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            ) t2 on hp.id=t2.hutang_piutang_id
            where date_format(pb.tanggal,'%Y-%m-%d')=:tanggal
            group by pb.id
            having sum(ifnull(t1.jumlah,0)) + sum(ifnull(t2.jumlah,0)) < hp.jumlah
         ) t3
         join pembelian on t3.id=pembelian.id
         join profil on pembelian.profil_id=profil.id");


      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PEMBELIAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      return $command->queryAll();
   }

   private function _totalPembelianHutang($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(t3.jumlah-t3.jml_bayar) total
         from
         (
            select pb.id, hp.jumlah, sum(ifnull(t1.jumlah,0)+ifnull(t2.jumlah,0)) jml_bayar 
            from pembelian pb
            join hutang_piutang hp on pb.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            left join
            (
               select pd.* from penerimaan_detail pd
               join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            ) t1 on hp.id=t1.hutang_piutang_id
            left join
            (
               select pd.* from pengeluaran_detail pd
               join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            ) t2 on hp.id=t2.hutang_piutang_id
            where date_format(pb.tanggal,'%Y-%m-%d')=:tanggal
            group by pb.id
            having sum(ifnull(t1.jumlah,0)) + sum(ifnull(t2.jumlah,0)) < hp.jumlah
         ) t3");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PEMBELIAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      $hutangPembelian = $command->queryRow();
      return $hutangPembelian['total'];
   }

   /**
    * Pembelian yang dibayar pada tanggal $tanggal, per nomor pembelian
    * @param date $tanggal
    * @return array nomor pembelian, nama profil, tanggal pembelian, total pembayaran
    */
   private function _pembelianBayar($tanggal) {
      $command = Yii::app()->db->createCommand("
         select pembelian.nomor, profil.nama, pembelian.tanggal, t2.total_bayar
         from
         (
            select id, sum(jumlah_bayar) total_bayar
            from
            (
               select sum(pd.jumlah) jumlah_bayar, pembelian.id
               from pengeluaran_detail pd
               join pengeluaran on pd.pengeluaran_id = pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
               join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
               join pembelian on hp.id=pembelian.hutang_piutang_id and date_format(pembelian.tanggal,'%Y-%m-%d')<:tanggal
               group by pembelian.id
               union
               select sum(pd.jumlah) jumlah_bayar, pembelian.id
               from penerimaan_detail pd
               join penerimaan on pd.penerimaan_id = penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
               join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
               join pembelian on hp.id=pembelian.hutang_piutang_id and date_format(pembelian.tanggal,'%Y-%m-%d')<:tanggal
               group by pembelian.id
            ) t1
            group by id
         ) t2
         join pembelian on t2.id=pembelian.id
         join profil on pembelian.profil_id = profil.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PEMBELIAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      return $command->queryAll();
   }

   private function _totalPembelianBayar($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(jumlah_bayar) total
         from
         (
            select sum(pd.jumlah) jumlah_bayar, pembelian.id
            from pengeluaran_detail pd
            join pengeluaran on pd.pengeluaran_id = pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join pembelian on hp.id=pembelian.hutang_piutang_id and date_format(pembelian.tanggal,'%Y-%m-%d')<:tanggal
            group by pembelian.id
            union
            select sum(pd.jumlah) jumlah_bayar, pembelian.id
            from penerimaan_detail pd
            join penerimaan on pd.penerimaan_id = penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=1
            join pembelian on hp.id=pembelian.hutang_piutang_id and date_format(pembelian.tanggal,'%Y-%m-%d')<:tanggal
            group by pembelian.id
         ) t1");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PEMBELIAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      $bayarPembelian = $command->queryRow();
      return $bayarPembelian['total'];
   }

   /**
    * Penjualan tunai yang terjadi pada tanggal $tanggal
    * @param date $tanggal Tanggal transaksi
    * @return array nomor, nama, jumlah dari penjualan tunai
    */
   private function _penjualanTunai($tanggal) {
      $command = Yii::app()->db->createCommand("
         select nomor, sum(jumlah) jumlah, profil.nama
         FROM
         (
            select penjualan.id, d.jumlah
            from penerimaan_detail d
            join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
            union
            select penjualan.id, d.jumlah
            from pengeluaran_detail d
            join pengeluaran p on d.pengeluaran_id = p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
         ) t 
         join penjualan on t.id = penjualan.id
         join profil on penjualan.profil_id = profil.id
         group by t.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PENJUALAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      return $command->queryAll();
   }

   /**
    * Total Penjualan Tunai pada tanggal $tanggal
    * @param date $tanggal Tanggal Trx
    * @return text Total penjualan tunai
    */
   private function _totalPenjualanTunai($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(jumlah) total
         FROM
         (
            select penjualan.id, d.jumlah
            from penerimaan_detail d
            join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
            union
            select penjualan.id, d.jumlah
            from pengeluaran_detail d
            join pengeluaran p on d.pengeluaran_id = p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
         ) t
         ");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PENJUALAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      $penjualanTunai = $command->queryRow();
      return $penjualanTunai['total'];
   }

   /**
    * Penjualan yang belum dibayar / belum lunas pada tanggal $tanggal
    * @param date $tanggal Tanggal trx
    * @return array penjualan_id, nomor (penjualan), nama (profil), jumlah (penjualan), jml_bayar (tunai)
    */
   private function _penjualanPiutang($tanggal) {
      $command = Yii::app()->db->createCommand("
         select penjualan.nomor, profil.nama, t3.jumlah, t3.jml_bayar
         from
         (
            select pj.id, hp.jumlah, sum(ifnull(t1.jumlah,0)+ifnull(t2.jumlah,0)) jml_bayar 
            from penjualan pj
            join hutang_piutang hp on pj.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            left join
            (
               select pd.* from penerimaan_detail pd
               join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            ) t1 on hp.id=t1.hutang_piutang_id
            left join
            (
               select pd.* from pengeluaran_detail pd
               join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            ) t2 on hp.id=t2.hutang_piutang_id
            where date_format(pj.tanggal,'%Y-%m-%d')=:tanggal
            group by pj.id
            having sum(ifnull(t1.jumlah,0)) + sum(ifnull(t2.jumlah,0)) < hp.jumlah
         ) t3
         join penjualan on t3.id=penjualan.id
         join profil on penjualan.profil_id=profil.id");


      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PENJUALAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      return $command->queryAll();
   }

   private function _totalPenjualanPiutang($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(t3.jumlah-t3.jml_bayar) total
         from
         (
            select pj.id, hp.jumlah, sum(ifnull(t1.jumlah,0)+ifnull(t2.jumlah,0)) jml_bayar 
            from penjualan pj
            join hutang_piutang hp on pj.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            left join
            (
               select pd.* from penerimaan_detail pd
               join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            ) t1 on hp.id=t1.hutang_piutang_id
            left join
            (
               select pd.* from pengeluaran_detail pd
               join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            ) t2 on hp.id=t2.hutang_piutang_id
            where date_format(pj.tanggal,'%Y-%m-%d')=:tanggal
            group by pj.id
            having sum(ifnull(t1.jumlah,0)) + sum(ifnull(t2.jumlah,0)) < hp.jumlah
         ) t3");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PENJUALAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      $penjualanPiutang = $command->queryRow();
      return $penjualanPiutang['total'];
   }

   /**
    * Pembayaran penjualan, baik lewat penerimaan maupun pengeluaran, untuk penjualan yang sudah lewat (sebelum tanggal $tanggal)
    * @param date $tanggal Tanggal trx pembayaran
    * @return array nomor (penjualan), nama (profil), jumlah_bayar (jumlah pembayaran)
    */
   private function _penjualanBayar($tanggal) {
      $command = Yii::app()->db->createCommand("
         select penjualan.nomor, profil.nama, t.jumlah_bayar
         from
         (
            select sum(pd.jumlah) jumlah_bayar, penjualan.id
            from penerimaan_detail pd
            join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join penjualan on hp.id=penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')<:tanggal
            group by penjualan.id
            union
            select sum(pd.jumlah) jumlah_bayar, penjualan.id
            from pengeluaran_detail pd
            join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join penjualan on hp.id=penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')<:tanggal
            group by penjualan.id
         ) t
         join penjualan on t.id=penjualan.id
         join profil on penjualan.profil_id=profil.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PENJUALAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      return $command->queryAll();
   }

   private function _totalPenjualanBayar($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(jumlah_bayar) total
         from
         (
            select sum(pd.jumlah) jumlah_bayar, penjualan.id
            from penerimaan_detail pd
            join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join penjualan on hp.id=penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')<:tanggal
            group by penjualan.id
            union
            select sum(pd.jumlah) jumlah_bayar, penjualan.id
            from pengeluaran_detail pd
            join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join penjualan on hp.id=penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')<:tanggal
            group by penjualan.id
         ) t
         join penjualan on t.id=penjualan.id
         join profil on penjualan.profil_id=profil.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PENJUALAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      $penjualanBayar = $command->queryRow();
      return $penjualanBayar['total'];
   }

   private function _marginPenjualanTunai($tanggal) {
      $command = Yii::app()->db->createCommand("
         select penjualan.nomor, profil.nama, jumlah_bayar, harga_beli, harga_jual, ((harga_jual - harga_beli)/harga_jual) * jumlah_bayar margin
         from
         (
            select t1.id, sum(jumlah) jumlah_bayar
            from
            (
               select penjualan.id, d.jumlah
               from penerimaan_detail d
               join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
               join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
               join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
               union
               select penjualan.id, d.jumlah
               from pengeluaran_detail d
               join pengeluaran p on d.pengeluaran_id = p.id and p.status=1 and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
               join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
               join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
            ) t1 
            group by t1.id
         ) t_bayar
         join
         (         
            select id, sum(harga_jual) harga_jual, sum(harga_beli) harga_beli
            from( 
               select penjualan.id, sum(jual_detail.harga_jual * hpp.qty) harga_jual,
               sum(hpp.harga_beli * hpp.qty) harga_beli
               from penerimaan_detail d
               join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
               join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
               join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
               join penjualan_detail jual_detail on penjualan.id = jual_detail.penjualan_id
               join harga_pokok_penjualan hpp on jual_detail.id=hpp.penjualan_detail_id
               group by penjualan.id
               union
               select penjualan.id, sum(jual_detail.harga_jual * hpp.qty) harga_jual,
               sum(hpp.harga_beli * hpp.qty) harga_beli
               from pengeluaran_detail d
               join pengeluaran p on d.pengeluaran_id = p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
               join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
               join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
               join penjualan_detail jual_detail on penjualan.id = jual_detail.penjualan_id
               join harga_pokok_penjualan hpp on jual_detail.id=hpp.penjualan_detail_id
               group by penjualan.id
            ) t2 group by id
         ) t_harga on t_bayar.id=t_harga.id
         join penjualan on t_bayar.id=penjualan.id
         join profil on penjualan.profil_id=profil.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PENJUALAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      return $command->queryAll();
   }

   private function _totalMarginPenjualanTunai($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(((harga_jual - harga_beli)/harga_jual) * jumlah_bayar) total
         from
         (
            select t1.id, sum(jumlah) jumlah_bayar
            from
            (
               select penjualan.id, d.jumlah
               from penerimaan_detail d
               join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
               join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
               join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
               union
               select penjualan.id, d.jumlah
               from pengeluaran_detail d
               join pengeluaran p on d.pengeluaran_id = p.id and p.status=1 and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
               join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
               join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
            ) t1 
            group by t1.id
         ) t_bayar
         join
         (         
            select id, sum(harga_jual) harga_jual, sum(harga_beli) harga_beli
            from( 
               select penjualan.id, sum(jual_detail.harga_jual * hpp.qty) harga_jual,
               sum(hpp.harga_beli * hpp.qty) harga_beli
               from penerimaan_detail d
               join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
               join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
               join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
               join penjualan_detail jual_detail on penjualan.id = jual_detail.penjualan_id
               join harga_pokok_penjualan hpp on jual_detail.id=hpp.penjualan_detail_id
               group by penjualan.id
               union
               select penjualan.id, sum(jual_detail.harga_jual * hpp.qty) harga_jual,
               sum(hpp.harga_beli * hpp.qty) harga_beli
               from pengeluaran_detail d
               join pengeluaran p on d.pengeluaran_id = p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
               join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
               join penjualan on hp.id = penjualan.hutang_piutang_id and date_format(penjualan.tanggal,'%Y-%m-%d')=:tanggal
               join penjualan_detail jual_detail on penjualan.id = jual_detail.penjualan_id
               join harga_pokok_penjualan hpp on jual_detail.id=hpp.penjualan_detail_id
               group by penjualan.id
            ) t2 group by id
         ) t_harga on t_bayar.id=t_harga.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_PENJUALAN,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      $margin = $command->queryRow();
      return $margin['total'];
   }

   private function _itemPengeluaran($tanggal) {
      $parents = ItemKeuangan::model()->findAll('parent_id is null');
      $itemArr = array();

      $command = Yii::app()->db->createCommand("
         select profil.nama, item.nama akun, pd.keterangan, pd.jumlah
         from penerimaan_detail pd
         join penerimaan p on pd.penerimaan_id=p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
         join item_keuangan item on pd.item_id=item.id and item.id > :itemTrx and parent_id = :parentId
         join profil on p.profil_id=profil.id
         where pd.posisi=:posisiPenerimaan
         union
         select profil.nama, item.nama, pd.keterangan, pd.jumlah
         from pengeluaran_detail pd
         join pengeluaran p on pd.pengeluaran_id=p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
         join item_keuangan item on pd.item_id=item.id and item.id > :itemTrx and parent_id = :parentId
         join profil on p.profil_id=profil.id
         where pd.posisi=:posisiPengeluaran");

      $commandTotal = Yii::app()->db->createCommand("
         select sum(jumlah) total
         from
         (
            select sum(pd.jumlah) jumlah
            from penerimaan_detail pd
            join penerimaan p on pd.penerimaan_id=p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join item_keuangan item on pd.item_id=item.id and item.id > :itemTrx and parent_id = :parentId
            join profil on p.profil_id=profil.id
            where pd.posisi=:posisiPenerimaan
            union
            select sum(pd.jumlah) jumlah
            from pengeluaran_detail pd
            join pengeluaran p on pd.pengeluaran_id=p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join item_keuangan item on pd.item_id=item.id and item.id > :itemTrx and parent_id = :parentId
            join profil on p.profil_id=profil.id
            where pd.posisi=:posisiPengeluaran
         ) t");

      foreach ($parents as $parent) {

         $command->bindValues(array(
             ':tanggal' => $tanggal,
             ':itemTrx' => ItemKeuangan::ITEM_TRX_SAJA,
             ':parentId' => $parent->id,
             ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
             ':statusPenerimaan' => Penerimaan::STATUS_BAYAR,
             ':posisiPengeluaran' => PengeluaranDetail::POSISI_DEBET,
             ':posisiPenerimaan' => PenerimaanDetail::POSISI_KREDIT,
         ));
         $commandTotal->bindValues(array(
             ':tanggal' => $tanggal,
             ':itemTrx' => ItemKeuangan::ITEM_TRX_SAJA,
             ':parentId' => $parent->id,
             ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
             ':statusPenerimaan' => Penerimaan::STATUS_BAYAR,
             ':posisiPengeluaran' => PengeluaranDetail::POSISI_DEBET,
             ':posisiPenerimaan' => PenerimaanDetail::POSISI_KREDIT,
         ));
         $jumlah = $commandTotal->queryRow();
         $itemArr[] = array(
             'id' => $parent->id,
             'nama' => $parent->nama,
             'total' => $jumlah['total'],
             'items' => $command->queryAll()
         );
      }
      return $itemArr;
   }

   private function _itemPenerimaan($tanggal) {
      $parents = ItemKeuangan::model()->findAll('parent_id is null');
      $itemArr = array();

      $command = Yii::app()->db->createCommand("
         select profil.nama, item.nama akun, pd.keterangan, pd.jumlah
         from penerimaan_detail pd
         join penerimaan p on pd.penerimaan_id=p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
         join item_keuangan item on pd.item_id=item.id and item.id > :itemTrx and parent_id = :parentId
         join profil on p.profil_id=profil.id
         where pd.posisi=:posisiPenerimaan
         union
         select profil.nama, item.nama, pd.keterangan, pd.jumlah
         from pengeluaran_detail pd
         join pengeluaran p on pd.pengeluaran_id=p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
         join item_keuangan item on pd.item_id=item.id and item.id > :itemTrx and parent_id = :parentId
         join profil on p.profil_id=profil.id
         where pd.posisi=:posisiPengeluaran");

      $commandTotal = Yii::app()->db->createCommand("
         select sum(jumlah) total
         from
         (
            select sum(pd.jumlah) jumlah
            from penerimaan_detail pd
            join penerimaan p on pd.penerimaan_id=p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join item_keuangan item on pd.item_id=item.id and item.id > :itemTrx and parent_id = :parentId
            join profil on p.profil_id=profil.id
            where pd.posisi=:posisiPenerimaan
            union
            select sum(pd.jumlah) jumlah
            from pengeluaran_detail pd
            join pengeluaran p on pd.pengeluaran_id=p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join item_keuangan item on pd.item_id=item.id and item.id > :itemTrx and parent_id = :parentId
            join profil on p.profil_id=profil.id
            where pd.posisi=:posisiPengeluaran
         ) t");

      foreach ($parents as $parent) {

         $command->bindValues(array(
             ':tanggal' => $tanggal,
             ':itemTrx' => ItemKeuangan::ITEM_TRX_SAJA,
             ':parentId' => $parent->id,
             ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
             ':statusPenerimaan' => Penerimaan::STATUS_BAYAR,
             ':posisiPengeluaran' => PengeluaranDetail::POSISI_KREDIT,
             ':posisiPenerimaan' => PenerimaanDetail::POSISI_DEBET,
         ));
         $commandTotal->bindValues(array(
             ':tanggal' => $tanggal,
             ':itemTrx' => ItemKeuangan::ITEM_TRX_SAJA,
             ':parentId' => $parent->id,
             ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
             ':statusPenerimaan' => Penerimaan::STATUS_BAYAR,
             ':posisiPengeluaran' => PengeluaranDetail::POSISI_KREDIT,
             ':posisiPenerimaan' => PenerimaanDetail::POSISI_DEBET,
         ));
         $jumlah = $commandTotal->queryRow();
         $itemArr[] = array(
             'id' => $parent->id,
             'nama' => $parent->nama,
             'total' => $jumlah['total'],
             'items' => $command->queryAll()
         );
      }
      return $itemArr;
   }

   private function _returBeliTunai($tanggal) {
      $command = Yii::app()->db->createCommand("
         select nomor, sum(jumlah) jumlah, profil.nama
         FROM
         (
            select retur_pembelian.id, d.jumlah
            from penerimaan_detail d
            join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join retur_pembelian on hp.id = retur_pembelian.hutang_piutang_id and date_format(retur_pembelian.tanggal,'%Y-%m-%d')=:tanggal
            union
            select retur_pembelian.id, d.jumlah
            from pengeluaran_detail d
            join pengeluaran p on d.pengeluaran_id = p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join retur_pembelian on hp.id = retur_pembelian.hutang_piutang_id and date_format(retur_pembelian.tanggal,'%Y-%m-%d')=:tanggal
         ) t 
         join retur_pembelian on t.id = retur_pembelian.id
         join profil on retur_pembelian.profil_id = profil.id
         group by t.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_BELI,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      return $command->queryAll();
   }

   private function _totalReturBeliTunai($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(jumlah) total
         FROM
         (
            select retur_pembelian.id, d.jumlah
            from penerimaan_detail d
            join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join retur_pembelian on hp.id = retur_pembelian.hutang_piutang_id and date_format(retur_pembelian.tanggal,'%Y-%m-%d')=:tanggal
            union
            select retur_pembelian.id, d.jumlah
            from pengeluaran_detail d
            join pengeluaran p on d.pengeluaran_id = p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join retur_pembelian on hp.id = retur_pembelian.hutang_piutang_id and date_format(retur_pembelian.tanggal,'%Y-%m-%d')=:tanggal
         ) t
         ");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_BELI,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      $returBeliTunai = $command->queryRow();
      return $returBeliTunai['total'];
   }

   private function _returBeliPiutang($tanggal) {
      $command = Yii::app()->db->createCommand("
         select rb.nomor, profil.nama, t3.jumlah-t3.jml_bayar jumlah
         from
         (
            select rp.id, hp.jumlah, sum(ifnull(t1.jumlah,0)+ifnull(t2.jumlah,0)) jml_bayar 
            from retur_pembelian rp
            join hutang_piutang hp on rp.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            left join
            (
               select pd.* from penerimaan_detail pd
               join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            ) t1 on hp.id=t1.hutang_piutang_id
            left join
            (
               select pd.* from pengeluaran_detail pd
               join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            ) t2 on hp.id=t2.hutang_piutang_id
            where date_format(rp.tanggal,'%Y-%m-%d')=:tanggal
            group by rp.id
            having sum(ifnull(t1.jumlah,0)) + sum(ifnull(t2.jumlah,0)) < hp.jumlah
         ) t3
         join retur_pembelian rb on t3.id=rb.id
         join profil on rb.profil_id=profil.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_BELI,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      return $command->queryAll();
   }

   private function _totalReturBeliPiutang($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(t3.jumlah-t3.jml_bayar) total
         from
         (
            select rp.id, hp.jumlah, sum(ifnull(t1.jumlah,0)+ifnull(t2.jumlah,0)) jml_bayar 
            from retur_pembelian rp
            join hutang_piutang hp on rp.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            left join
            (
               select pd.* from penerimaan_detail pd
               join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            ) t1 on hp.id=t1.hutang_piutang_id
            left join
            (
               select pd.* from pengeluaran_detail pd
               join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            ) t2 on hp.id=t2.hutang_piutang_id
            where date_format(rp.tanggal,'%Y-%m-%d')=:tanggal
            group by rp.id
            having sum(ifnull(t1.jumlah,0)) + sum(ifnull(t2.jumlah,0)) < hp.jumlah
         ) t3
         ");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_BELI,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      $piutangReturBeli = $command->queryRow();
      return $piutangReturBeli['total'];
   }

   private function _returBeliBayar($tanggal) {
      $command = Yii::app()->db->createCommand("
         select retur_pembelian.nomor, profil.nama, t.jumlah_bayar
         from
         (
            select sum(pd.jumlah) jumlah_bayar, retur_pembelian.id
            from penerimaan_detail pd
            join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join retur_pembelian on hp.id=retur_pembelian.hutang_piutang_id and date_format(retur_pembelian.tanggal,'%Y-%m-%d')<:tanggal
            group by retur_pembelian.id
            union
            select sum(pd.jumlah) jumlah_bayar, retur_pembelian.id
            from pengeluaran_detail pd
            join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join retur_pembelian on hp.id=retur_pembelian.hutang_piutang_id and date_format(retur_pembelian.tanggal,'%Y-%m-%d')<:tanggal
            group by retur_pembelian.id
         ) t
         join retur_pembelian on t.id=retur_pembelian.id
         join profil on retur_pembelian.profil_id=profil.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_BELI,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      return $command->queryAll();
   }

   private function _totalReturBeliBayar($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(t.jumlah_bayar) total
         from
         (
            select sum(pd.jumlah) jumlah_bayar, retur_pembelian.id
            from penerimaan_detail pd
            join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join retur_pembelian on hp.id=retur_pembelian.hutang_piutang_id and date_format(retur_pembelian.tanggal,'%Y-%m-%d')<:tanggal
            group by retur_pembelian.id
            union
            select sum(pd.jumlah) jumlah_bayar, retur_pembelian.id
            from pengeluaran_detail pd
            join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join retur_pembelian on hp.id=retur_pembelian.hutang_piutang_id and date_format(retur_pembelian.tanggal,'%Y-%m-%d')<:tanggal
            group by retur_pembelian.id
         ) t
         ");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_BELI,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      $bayarReturBeli = $command->queryRow();
      return $bayarReturBeli['total'];
   }

   private function _returJualTunai($tanggal) {
      $command = Yii::app()->db->createCommand("
         select nomor, sum(jumlah) jumlah, profil.nama
         FROM
         (
            select retur_penjualan.id, d.jumlah
            from penerimaan_detail d
            join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join retur_penjualan on hp.id = retur_penjualan.hutang_piutang_id and date_format(retur_penjualan.tanggal,'%Y-%m-%d')=:tanggal
            union
            select retur_penjualan.id, d.jumlah
            from pengeluaran_detail d
            join pengeluaran p on d.pengeluaran_id = p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join retur_penjualan on hp.id = retur_penjualan.hutang_piutang_id and date_format(retur_penjualan.tanggal,'%Y-%m-%d')=:tanggal
         ) t 
         join retur_penjualan on t.id = retur_penjualan.id
         join profil on retur_penjualan.profil_id = profil.id
         group by t.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_JUAL,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      return $command->queryAll();
   }

   private function _totalReturJualTunai($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(jumlah) total
         FROM
         (
            select retur_penjualan.id, d.jumlah
            from penerimaan_detail d
            join penerimaan p on d.penerimaan_id = p.id and p.status=:statusPenerimaan and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join retur_penjualan on hp.id = retur_penjualan.hutang_piutang_id and date_format(retur_penjualan.tanggal,'%Y-%m-%d')=:tanggal
            union
            select retur_penjualan.id, d.jumlah
            from pengeluaran_detail d
            join pengeluaran p on d.pengeluaran_id = p.id and p.status=:statusPengeluaran and date_format(p.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on d.hutang_piutang_id = hp.id and hp.asal=:asalHutangPiutang
            join retur_penjualan on hp.id = retur_penjualan.hutang_piutang_id and date_format(retur_penjualan.tanggal,'%Y-%m-%d')=:tanggal
         ) t
         ");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_JUAL,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      $returJualTunai = $command->queryRow();
      return $returJualTunai['total'];
   }

   private function _returJualHutang($tanggal) {
      $command = Yii::app()->db->createCommand("
         select rb.nomor, profil.nama, t3.jumlah-t3.jml_bayar jumlah
         from
         (
            select rp.id, hp.jumlah, sum(ifnull(t1.jumlah,0)+ifnull(t2.jumlah,0)) jml_bayar 
            from retur_penjualan rp
            join hutang_piutang hp on rp.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            left join
            (
               select pd.* from penerimaan_detail pd
               join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            ) t1 on hp.id=t1.hutang_piutang_id
            left join
            (
               select pd.* from pengeluaran_detail pd
               join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            ) t2 on hp.id=t2.hutang_piutang_id
            where date_format(rp.tanggal,'%Y-%m-%d')=:tanggal
            group by rp.id
            having sum(ifnull(t1.jumlah,0)) + sum(ifnull(t2.jumlah,0)) < hp.jumlah
         ) t3
         join retur_penjualan rb on t3.id=rb.id
         join profil on rb.profil_id=profil.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_JUAL,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      return $command->queryAll();
   }

   private function _totalReturJualHutang($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(t3.jumlah-t3.jml_bayar) total
         from
         (
            select rp.id, hp.jumlah, sum(ifnull(t1.jumlah,0)+ifnull(t2.jumlah,0)) jml_bayar 
            from retur_penjualan rp
            join hutang_piutang hp on rp.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            left join
            (
               select pd.* from penerimaan_detail pd
               join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            ) t1 on hp.id=t1.hutang_piutang_id
            left join
            (
               select pd.* from pengeluaran_detail pd
               join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            ) t2 on hp.id=t2.hutang_piutang_id
            where date_format(rp.tanggal,'%Y-%m-%d')=:tanggal
            group by rp.id
            having sum(ifnull(t1.jumlah,0)) + sum(ifnull(t2.jumlah,0)) < hp.jumlah
         ) t3");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_JUAL,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));
      $returJualHutang = $command->queryRow();
      return $returJualHutang['total'];
   }

   private function _returJualBayar($tanggal) {
      $command = Yii::app()->db->createCommand("
         select retur_penjualan.nomor, profil.nama, t.jumlah_bayar
         from
         (
            select sum(pd.jumlah) jumlah_bayar, retur_penjualan.id
            from penerimaan_detail pd
            join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join retur_penjualan on hp.id=retur_penjualan.hutang_piutang_id and date_format(retur_penjualan.tanggal,'%Y-%m-%d')<:tanggal
            group by retur_penjualan.id
            union
            select sum(pd.jumlah) jumlah_bayar, retur_penjualan.id
            from pengeluaran_detail pd
            join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join retur_penjualan on hp.id=retur_penjualan.hutang_piutang_id and date_format(retur_penjualan.tanggal,'%Y-%m-%d')<:tanggal
            group by retur_penjualan.id
         ) t
         join retur_penjualan on t.id=retur_penjualan.id
         join profil on retur_penjualan.profil_id=profil.id");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_JUAL,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      return $command->queryAll();
   }

   private function _totalReturJualBayar($tanggal) {
      $command = Yii::app()->db->createCommand("
         select sum(t.jumlah_bayar) total
         from
         (
            select sum(pd.jumlah) jumlah_bayar, retur_penjualan.id
            from penerimaan_detail pd
            join penerimaan on pd.penerimaan_id=penerimaan.id and penerimaan.status=:statusPenerimaan and date_format(penerimaan.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join retur_penjualan on hp.id=retur_penjualan.hutang_piutang_id and date_format(retur_penjualan.tanggal,'%Y-%m-%d')<:tanggal
            group by retur_penjualan.id
            union
            select sum(pd.jumlah) jumlah_bayar, retur_penjualan.id
            from pengeluaran_detail pd
            join pengeluaran on pd.pengeluaran_id=pengeluaran.id and pengeluaran.status=:statusPengeluaran and date_format(pengeluaran.tanggal,'%Y-%m-%d')=:tanggal
            join hutang_piutang hp on pd.hutang_piutang_id=hp.id and hp.asal=:asalHutangPiutang
            join retur_penjualan on hp.id=retur_penjualan.hutang_piutang_id and date_format(retur_penjualan.tanggal,'%Y-%m-%d')<:tanggal
            group by retur_penjualan.id
         ) t
         ");

      $command->bindValues(array(
          ':tanggal' => $tanggal,
          ':asalHutangPiutang' => HutangPiutang::DARI_RETUR_JUAL,
          ':statusPengeluaran' => Pengeluaran::STATUS_BAYAR,
          ':statusPenerimaan' => Penerimaan::STATUS_BAYAR
      ));

      $bayarReturJual = $command->queryRow();
      return $bayarReturJual['total'];
   }

}
