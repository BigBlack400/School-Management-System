# Data Privacy and Retention Policy

**Owner:** School leadership and the designated privacy/security officer  
**Applies to:** Student, guardian, staff, academic, attendance, admissions, and financial information processed through CampusOS  
**Review:** Annually, after a material incident, or when law/contracts change

## 1. Scope and legal review

This policy is a practical baseline for a school-operated information system. It does not by itself establish GDPR or FERPA compliance. The school must identify its legal role (including controller/processor or covered institution/school official status), applicable national and local law, student-age rules, records obligations, processor contracts, and notice/consent requirements with qualified counsel and its privacy officer.

## 2. Data minimization and use

Collect only information needed for a defined educational, safeguarding, administrative, or accounting purpose. Define each data category, source, authorized use, access group, retention period, and deletion/archiving action in a maintained data inventory. Do not place special-category, medical, disability, immigration, financial-account, or similarly sensitive details in general-purpose fields unless an approved workflow and access control exist.

Use student and guardian data only for the stated school purpose or another legally permitted purpose. Do not use records for advertising, unrelated profiling, sale, or public disclosure. Secondary use, analytics, research, or vendor access requires documented approval, minimization/de-identification where appropriate, and a legal/contractual basis.

## 3. Security safeguards

- Use named accounts, role-based least privilege, school/record-level authorization, periodic access reviews, and prompt deprovisioning.
- Require strong password hashing and secure reset processes; protect sessions with HTTPS, HttpOnly/SameSite cookies, CSRF defenses, expiry, and session rotation.
- Encrypt network traffic with modern TLS. Encrypt database volumes, backups, and private document storage at rest using managed keys; restrict key access and rotate keys under a documented schedule.
- Store uploaded documents outside the public web root, use generated object keys, validate file type and size, scan for malware, and log authorized downloads.
- Use prepared queries, output encoding, input validation, security updates, vulnerability testing, and production-safe error handling.
- Limit audit logs to necessary metadata; never log passwords, reset tokens, session IDs, full payment credentials, or unnecessary student details. Protect logs from alteration and restrict access.
- Use de-identified or synthetic data for non-production environments. Apply equivalent controls to processors and backups.

## 4. Access, correction, and disclosure

Verify identity and legal authority before fulfilling access, correction, deletion, portability, restriction, or disclosure requests. Route requests to the school's designated records/privacy contact; authenticate guardians and consider student age, custody restrictions, safety concerns, and applicable law before disclosure. Maintain a record of request, verification, decision, action, and response deadlines.

Disclose only the minimum authorized information to verified recipients over approved channels. Review exports and report cards for scope and accuracy. Record legally required disclosures and processor access. Do not promise deletion where statutory retention, legal hold, safeguarding, or accounting duties require preservation; document the basis and limit access.

## 5. Retention and disposal

The school must approve a jurisdiction-specific retention schedule covering each record category. Example categories include:

| Record category | Baseline handling |
|---|---|
| Active student profile, guardians, admissions, enrollment history | Retain while active; after graduation/withdrawal, archive for the period required by education, safeguarding, and limitation laws, then securely delete or irreversibly de-identify. |
| Attendance, grades, exams, report cards, promotion/transfer history | Retain for the approved academic-record period; restrict after the student leaves; preserve only the official record for the legally required duration. |
| Invoices, payments, receipts, and accounting audit | Retain for statutory tax/accounting and dispute periods, then securely dispose of personal identifiers where legally permitted. |
| Uploaded identity/supporting documents | Retain only as long as needed for the verified admissions/administrative purpose; delete when purpose expires unless a documented legal hold applies. |
| Authentication, security, and audit logs | Retain for a documented short security/accountability period based on risk and legal requirements; restrict and purge on schedule. |
| Password reset and login-throttle records | Expire tokens promptly; purge expired reset tokens and old throttle records through scheduled maintenance. |

Graduation or withdrawal triggers an authorized status change, review of access and active integrations, notice/record handling as required, and scheduled deletion or archival. Deletion must cover primary records, files, replicas, exports, and eventually backups under the backup expiry cycle. Maintain a deletion log with identifiers minimized and the action/date/basis. Suspend routine deletion only for a documented legal hold or investigation and review the hold periodically.

## 6. Incident response and vendors

Report suspected loss, unauthorized access, ransomware, misdirected messages, or improper disclosure immediately to the designated incident contact. Preserve evidence, limit access, assess affected records and people, document actions, and notify individuals, regulators, and partners within applicable legal/contractual timeframes. Do not independently investigate or make external statements without authorization.

Approve vendors through privacy/security review, written data-processing terms, confidentiality, subprocessor transparency, breach notification, access/deletion assistance, data location/transfer review, and return/deletion commitments. Review vendor access periodically and remove it when no longer needed.

## 7. Accountability

Assign named owners for records, security, incident response, and retention. Train staff and contractors before granting access and annually thereafter. Review permissions, processor agreements, retention execution, and policy effectiveness at least annually. Document exceptions, approvals, and remediation dates.
