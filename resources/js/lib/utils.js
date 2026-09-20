import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}

export function terbilang(angka) {
  if (!angka || isNaN(angka) || Number(angka) <= 0) return "";
  const num = Math.floor(Math.abs(Number(angka)));
  const huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
  let hasil = "";

  if (num < 12) {
    hasil = huruf[num];
  } else if (num < 20) {
    hasil = terbilang(num - 10) + " Belas";
  } else if (num < 100) {
    hasil = terbilang(Math.floor(num / 10)) + " Puluh " + terbilang(num % 10);
  } else if (num < 200) {
    hasil = "Seratus " + terbilang(num - 100);
  } else if (num < 1000) {
    hasil = terbilang(Math.floor(num / 100)) + " Ratus " + terbilang(num % 100);
  } else if (num < 2000) {
    hasil = "Seribu " + terbilang(num - 1000);
  } else if (num < 1000000) {
    hasil = terbilang(Math.floor(num / 1000)) + " Ribu " + terbilang(num % 1000);
  } else if (num < 1000000000) {
    hasil = terbilang(Math.floor(num / 1000000)) + " Juta " + terbilang(num % 1000000);
  } else if (num < 1000000000000) {
    hasil = terbilang(Math.floor(num / 1000000000)) + " Miliar " + terbilang(num % 1000000000);
  } else if (num < 1000000000000000) {
    hasil = terbilang(Math.floor(num / 1000000000000)) + " Triliun " + terbilang(num % 1000000000000);
  }

  return hasil.replace(/\s+/g, " ").trim();
}
