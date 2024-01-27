<?php

namespace Drupal\esm_test_base;

enum StatusBadgeStatus: string {
  case Success = 'success';
  case Info = 'info';
  case Warning = 'warning';
  case Error = 'error';
}
