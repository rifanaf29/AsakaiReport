1. Template Normal
	Attributes: 
	1. Target
	2. Actual
	No additional field

Department use and titles: 
PPIC:
On Time Kedatangan Subcont (unit: %, target: 100%)
On Time Preparation (unit: %, target:100%, higher is better )
Mistake Delivery (unit: case, target:0 case, lower is better)
Cripple (unit: ppm, target:0 ppm, lower is better)
Line Stop (unit: min, target = 0 min, lowe is better)
On Time Delivery (unit: %, target =100%, higher is better )
Mis Part (unit: ppm, target:0 ppm, lower is better )

PD:
OEE (unit: %, target: 85,0%, higher is better)

QC:
Warranty Claim (Unit: case, target: 0 case, lower is better)
Plan vs Actual Final Inspect (unit: %, target= 100%, higher is better)

MN:
Downtime Machine MN (unit: %, target= 0.88%, lower is better)
MTTR MN (unit: Hour, target = 2 hour, lower is better)
MTBF MN (unit: Hour, target = 400 hour, lower is better)

HR:
Absenteism (unit: %, target: 0.6%, lower is better)
Tunjuk Kanan Kiri Sebelum Menyebrang (unit: %, target: 100%, higher is better)
Naik Turun Tangga Memegang Handrail (unit: %, target: 100%, higher is better)
Tidak Menggunakan Ponsel Saat Berjalan (unit: %, target: 100%, higher is better)
Tidak Memasukkan Tangan ke Dalam Saku (unit: %, target: 100%, higher is better)
Berjalan di Area Pejalan Kaki (unit: %, target: 100%, higher is better)

MK:
On Time Receiving Order (unit: %, target = 100%, higher is better)

PS:
On Time Delivery Supplier (unit: %, target = 89%, higher is better)

2. Special Template
	Attributes: 
	1. Target
	2. Actual (average all additional fields)
	3. additional field (PD1 to PD5)
Dept use:

PPIC: 
Plan vs Actual PD (unit all fields: %, target:100%, higher is better)
Level Stock (unit all fields : Day, target:3 day, higher is better )

3. PPIC Special Template
	Shortage
	Attr: Target(ppm), Actual(ppm), Order(Pcs), Shortage(Pcs)

PD Special Template:
1. Template Rejection in Proses
	Attributes: 
	1. Target (ppm) => target 2026 = 68
	2. Actual (ppm) ->using formula (Actual NG (Pcs)/Actual Produksi(Pcs))*1000000
	3. Actual NG (pcs)
	4. Actual Produksi (Pcs)
2. Man Power Productivity
	Attr:
	1. Target (unit: pmh direct, target 130, higher is better)
	2. Actual (unit: pmh direct)
	3. Target (unit: pmh direct + in direct, target 108, higher is better)
	4. Actual (unit: pmh direct + in direct)

3. Waste CNC Bending
	Attr: 
	1. No Target
	2. Hasil Produksi (unit: kg)
	3. CB1 (unit:kg)
	4. CB2 (unit:kg)
	5. CB3 (unit:kg)
	6. CB4 (unit:kg)
	7. Total Waste (kg) (Sum CB1 to CB4 each day)
	8. Total Waste (%)  formula: total waste / hasil produksi
	9. D6 (Rp)
	10. D7 (Rp)
	11. D8 (Rp)
	12. D9 (Rp)
	13. D11 (Rp)
	14. D12 (Rp)
	15. D13 (Rp)
	16. CopQ Material (Rp) Formula Sum D6 to D13 each day
	Addition Column:
	1. Avg (Rp) for D6 to D13
	2. Avg/D (Rp) Average From row Hasil Produksi to Total Waste (get value from every day) AND D6 to CopQ material is Sum of Every Date/Day

4. MP & OT
	Attr:
	1. Jumlah MP (unit :Orang)
	2. Jam Kerja NOrmal (Unit: Jam)
	3. Jam OT (Unit: Jam)
	4. OT Charge PD1 (Unit Rp)
	5. OT Charge PD2 (Unit Rp)
	6. OT Charge PD3 (Unit Rp)
	7. OT Charge PD4 (Unit Rp)
	8. OT Charge PD5 (Unit Rp)
	9. Total OT Charge (Unit: RP)
	10. Sales Amount (Unit: Rp)
	11. Target Sales (unit: Rp) 
	12. Achivement (Unit: %)

	
HR Special Template:
1. Column Waste -> GRAM, NG, Puntungan)
2. Field Total -> Sum of GRAM, NG, Puntungan per date)
3. Field Hasil Produksi

1. Waste Gram
	Attr:
	1. Target (unit: kg, 970,7 kg, lower is better)
	2. Actual (get total sum from additional field -> PD1 to Workshop)
	3. PD1 (kg)
	4. PD2 (Kg)
	5. PD3 (kg)
	6. PD4 (kg)
	7. Workshop (kg)
	8. percentage (%) -> calculated actual/hasil produksi
	9. Total Column -> Sum of each field for all date

2. Waste NG
	Attr:
	1. Target (unit: kg, 42.5 kg, lower is better)
	2. Actual (get total sum from additional field -> PD1 to EG)
	3. PD1 (kg)
	4. PD2 (Kg)
	5. PD3 (kg)
	6. PD4 (kg)
	7. QA (kg)
	8. EG (kg)
	9. percentage (%) -> calculated total/hasil produksi
	10. Total Column -> Sum of each field for all date

3. Waste Puntungan
	Attr:
	1. Target (unit: kg, 42.5 kg, lower is better)
	2. Actual (get total sum from additional field -> PD1 and P3)
	3. PD1 (kg)
	4. PD3 (kg)
	5. percentage (%) -> calculated total/hasil produksi
	6. Total Column -> Sum of each field for all date


4. Main KPI
	Attr:
	1. Target (unit: case, 0 case, lower is better)
	2. Actual (sum of additional field)
	2. Fatal Accident (Rank A) (unit: case)
	3. LWD Accident (Rank B) (unit: case)
	4. First Aid (Rank C) (unit: case)
	
Quality special template:
1. Template Rejection 1
	Attribute:
	1. Target (unit: ppm, target:10 ppm, lower is better)
	2. Actual (Formula = (actual NG/Actual kedatangan)*1000000)
	3. Actual kedatangan (pcs)
	4. Actual NG (pcs)
	5. Total Supp
	use in: 
	1. Rejection Incoming BB-SBB
	2. Rejection Incoming PL
	
2. Template Rejection FI_NC
	Attribute:
	1. Target NC (unit: ppm, target: 3000, lower is better)
	2. Actual NC (Formula = (actual NC/Actual Pengecekan)*1000000)
	3. Actual Pengecekan (pcs) 
	4. Actual NC (pcs)

3. Template Rejection FI_NG
	Attribute:
	1. Target NG (unit: ppm, target: 3000, lower is better)
	2. Actual NG (Formula = (actual NG/Actual Pengecekan)*1000000)
	3. Actual Pengecekan (pcs) 
	4. Actual NG (pcs)

4. Template Claim Cust
	Attribute:
	1. Target (unit: ppm, target: 5 ppm, lower is better)
	2. Actual (Formula = (actual NG/Actual Pengecekan)*1000000)
	3. Actual Delv (pcs)
	4. Actual NG (Pcs) 
	5. Total Cust

5. CopQ (Money related)
    Attribute:
    1. Target (unit: Rp, target: Rp.0, higher is better)
    2. Actual (Sum additional fields from QA tugas luar to Waste PD3)
    3. QA Tugas Luar
    4. FI
    3. PD1
    4. PD2
    5. PD3
    6. PD4
    7. PD5
    8. Supp
    9. Waste PD3
    10. Total column