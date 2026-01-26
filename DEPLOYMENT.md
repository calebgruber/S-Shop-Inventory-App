# Deployment Checklist

Use this checklist when deploying the S-Shop Inventory System to production.

## Pre-Deployment

### 1. Server Requirements
- [ ] PHP 7.4+ installed
- [ ] MySQL 5.7+ installed
- [ ] Apache or Nginx web server running
- [ ] MySQLi extension enabled
- [ ] GD extension enabled (for barcodes)
- [ ] File write permissions available

### 2. Database Setup
- [ ] MySQL server is accessible
- [ ] Database credentials confirmed:
  - Host: localhost
  - Database: voxelnodes_sshop
  - User: voxelnodes_sshop
  - Password: ).sBi.*B=}rp
- [ ] Run database schema:
  ```bash
  mysql -u voxelnodes_sshop -p < database_schema.sql
  ```
- [ ] Verify all tables created (11 tables)
- [ ] Verify sample data inserted

### 3. File Deployment
- [ ] All files uploaded to web server
- [ ] config.php contains correct database credentials
- [ ] .htaccess in place (for Apache)
- [ ] Directory structure created:
  ```bash
  mkdir -p uploads pdfs assets
  ```
- [ ] Directory permissions set:
  ```bash
  chmod 755 uploads pdfs assets
  chown www-data:www-data uploads pdfs assets  # Apache
  # OR
  chown nginx:nginx uploads pdfs assets  # Nginx
  ```

### 4. Web Server Configuration

#### For Apache:
- [ ] Document root points to application directory
- [ ] .htaccess processing enabled (AllowOverride All)
- [ ] mod_rewrite enabled (if needed)
- [ ] PHP module loaded
- [ ] Test: `http://your-domain.com/` loads

#### For Nginx:
- [ ] PHP-FPM configured and running
- [ ] Server block configured with correct root
- [ ] PHP processing enabled
- [ ] Test: `http://your-domain.com/` loads

## Initial Setup

### 5. Application Configuration
- [ ] Access application URL in browser
- [ ] Dashboard loads without errors
- [ ] Navigate to Settings
- [ ] Upload company logo (PNG/JPG)
- [ ] Verify logo appears in settings
- [ ] Toggle dark/light mode - verify it works

### 6. Initial Data Setup
- [ ] Review categories (add/remove as needed)
- [ ] Review theatre spaces (add/remove as needed)
- [ ] Add first inventory item as test
- [ ] Verify barcode generates automatically
- [ ] Create test show
- [ ] Create test pull sheet (draft)

## Testing

### 7. Functionality Testing
- [ ] Create a show
- [ ] Build a pull sheet with items
- [ ] Finalize pull sheet (click "Build Show")
- [ ] Verify PDF417 barcode generated
- [ ] Download PDF - verify logo and barcode appear
- [ ] Enter Pick Mode
- [ ] Enter name in modal
- [ ] Scan/enter pull sheet barcode
- [ ] Verify items display as red cards
- [ ] Scan an item barcode
- [ ] Verify audio plays (enable browser sound)
- [ ] Verify card turns yellow/green
- [ ] Complete pick
- [ ] Enter Return Mode
- [ ] Scan pull sheet barcode
- [ ] Return items
- [ ] Verify inventory updates

### 8. Reports Testing
- [ ] Generate "All Items" report
- [ ] Verify items display correctly
- [ ] Download PDF
- [ ] Verify logo appears on PDF
- [ ] Test "By Show" filter
- [ ] Test "By Theatre Space" filter
- [ ] Generate Avery 8195 barcode labels
- [ ] Verify label format (3x4 grid)

### 9. Security Testing
- [ ] Verify .htaccess blocks access to config.php
  - Try: `http://your-domain.com/config.php`
  - Should get 403 Forbidden
- [ ] Test SQL injection prevention:
  - Enter: `' OR '1'='1` in search fields
  - Should not cause errors or security issues
- [ ] Test XSS prevention:
  - Enter: `<script>alert('test')</script>` in form fields
  - Should be escaped and display as text

### 10. Performance Testing
- [ ] Add 20+ inventory items
- [ ] Create 5+ shows
- [ ] Build 5+ pull sheets
- [ ] Verify search remains fast
- [ ] Verify page loads are quick
- [ ] Test on mobile device
- [ ] Test on tablet

## Production Readiness

### 11. Error Handling
- [ ] In config.php, disable error display for production:
  ```php
  error_reporting(0);
  ini_set('display_errors', 0);
  ```
- [ ] Configure error logging:
  ```php
  ini_set('log_errors', 1);
  ini_set('error_log', '/path/to/php-error.log');
  ```
- [ ] Test that errors are logged, not displayed

### 12. Backup Strategy
- [ ] Set up automated database backups:
  ```bash
  # Example daily backup cron job
  0 2 * * * mysqldump -u voxelnodes_sshop -p'password' voxelnodes_sshop > /backups/sshop_$(date +\%Y\%m\%d).sql
  ```
- [ ] Set up file backups (uploads, pdfs, assets)
- [ ] Test restore procedure
- [ ] Document backup location

### 13. Monitoring
- [ ] Set up error log monitoring
- [ ] Set up disk space monitoring (pdfs/ can grow)
- [ ] Set up MySQL monitoring
- [ ] Configure alerts for critical errors

### 14. Documentation
- [ ] Print QUICKREF.md for users
- [ ] Print TESTING.md for QA team
- [ ] Save admin documentation (INSTALL.md)
- [ ] Document server locations and credentials (securely)

## User Training

### 15. Staff Training
- [ ] Train administrators on:
  - Adding inventory
  - Managing categories/spaces
  - Uploading logos
- [ ] Train shop leads on:
  - Creating shows
  - Building pull sheets
  - Generating reports
- [ ] Train shop staff on:
  - Pick mode
  - Return mode
  - Scanning barcodes

### 16. User Documentation
- [ ] Distribute QUICKREF.md to all users
- [ ] Post common workflows in shop area
- [ ] Create video tutorials (optional)
- [ ] Schedule Q&A session

## Go-Live

### 17. Final Checks
- [ ] All training complete
- [ ] All initial data entered
- [ ] All barcodes printed for existing inventory
- [ ] Backup system verified
- [ ] Support plan in place
- [ ] Emergency rollback plan ready

### 18. Launch
- [ ] Announce go-live date to staff
- [ ] Switch to production mode
- [ ] Monitor for first 24 hours
- [ ] Address any immediate issues
- [ ] Collect user feedback

## Post-Deployment

### 19. First Week
- [ ] Daily check of error logs
- [ ] Daily check of user feedback
- [ ] Address any usability issues
- [ ] Verify backups are running
- [ ] Monitor disk space usage

### 20. Ongoing Maintenance
- [ ] Weekly review of system logs
- [ ] Monthly backup verification
- [ ] Monthly database cleanup (old transactions)
- [ ] Quarterly user satisfaction survey
- [ ] Update documentation as needed

## Troubleshooting Reference

### Common Issues

**Database Connection Failed**
- Check MySQL is running: `systemctl status mysql`
- Verify credentials in config.php
- Check database exists: `SHOW DATABASES;`
- Verify user permissions: `SHOW GRANTS FOR 'voxelnodes_sshop'@'localhost';`

**Can't Upload Logo**
- Check directory exists: `ls -la uploads/`
- Check permissions: `chmod 755 uploads`
- Check owner: `chown www-data:www-data uploads`
- Check PHP upload settings in php.ini

**PDFs Not Generating**
- Check directory exists: `ls -la pdfs/`
- Check permissions: `chmod 755 pdfs`
- Check disk space: `df -h`
- Check PHP error log

**Barcodes Not Displaying**
- Verify GD extension: `php -m | grep gd`
- Check barcode_generator.php for errors
- View browser console for JavaScript errors

**Audio Not Playing**
- Verify browser allows audio
- Check browser console for errors
- Test on different browser
- Ensure user interaction before audio plays

## Support Contacts

- **System Administrator**: ___________________
- **Database Administrator**: ___________________
- **Primary Contact**: ___________________
- **Emergency Contact**: ___________________

## Sign-Off

### Deployment Team
- [ ] Developer: __________________ Date: __________
- [ ] QA Tester: _________________ Date: __________
- [ ] Database Admin: ____________ Date: __________
- [ ] System Admin: ______________ Date: __________

### Approval
- [ ] Project Manager: ___________ Date: __________
- [ ] Department Head: ___________ Date: __________

---

**Deployment Date**: __________________
**Go-Live Date**: __________________
**Version**: 1.0.0
**Status**: ☐ In Progress  ☐ Complete

---

Keep this checklist for future reference and updates.
