# Backup and Disaster Recovery Policy

**Owner:** IT operations lead, with approval from school leadership  
**Applies to:** Production database, private documents, configuration, encryption keys, and application deployment artifacts  
**Review:** At least annually and after every major recovery exercise or incident

## 1. Objectives and ownership

The school must assign primary and alternate recovery owners, maintain current contact and escalation details, and define service-specific recovery time objective (RTO) and recovery point objective (RPO) based on educational, safeguarding, and financial impact. Until formally approved, use a target RPO of 24 hours and RTO of one business day only as planning assumptions—not service guarantees.

Backups support availability; they do not replace access controls, retention requirements, or incident response. Keep backup encryption keys separate from backup storage and make them available to authorized recovery personnel through a tested escrow/secret-management process.

## 2. Schedule and retention tiers

For production, implement and monitor at least:

- **Database:** encrypted automated daily full backup and binary-log/point-in-time recovery where supported; retain daily copies for 35 days.
- **Weekly:** encrypted weekly recovery point retained for 12 weeks.
- **Monthly:** encrypted monthly recovery point retained for 12 months, or longer only when the approved legal/accounting schedule requires it.
- **Documents and configuration:** daily incremental or object-version backups with a consistent manifest; retain in line with approved records policy.
- **Off-site resilience:** replicate encrypted backups to a separate account/security boundary and a geographically separate region, protected from production credentials and deletion.

Set schedules and retention in managed backup tooling rather than relying on a developer workstation. Define an immutable/write-protected tier where available. Remove expired backups automatically and record completion, verification, retention expiry, and exceptions. Never extend backup retention indefinitely by default.

## 3. Backup controls

- Use a dedicated least-privilege backup identity and encryption in transit and at rest.
- Exclude temporary caches and unnecessary local logs; include the database, private documents, required configuration, and recovery metadata.
- Do not place database passwords, encryption keys, or other secrets in plain-text backup scripts or logs.
- Record backup start/end, size, checksum or provider verification, destination, and result without logging sensitive records.
- Alert the operations owner on failure, missed schedule, tampering, or capacity limits. Investigate and restore a valid recovery point.
- Protect backups against ransomware and compromised production credentials; require separate authorization for destructive backup administration.

## 4. Restoration and disaster response

1. Declare the incident and appoint an incident commander. Identify affected systems, data-loss window, legal holds, and whether backup credentials/keys may be compromised.
2. Isolate compromised hosts and credentials. Do not overwrite evidence or restore into a potentially compromised environment.
3. Select a verified clean recovery point based on RPO and business/legal needs. Obtain the required approvals and document the selected point.
4. Provision a patched, isolated environment; retrieve keys through the approved recovery process; restore database and documents; verify schema/version consistency and referential integrity.
5. Rotate database, application, mail, storage, and administrative credentials as appropriate. Revoke active sessions by incrementing `users.auth_version` or otherwise invalidating sessions.
6. Run integrity checks and functional/security smoke tests. Verify school scope, critical attendance/grade/finance records, user permissions, and document access before reopening.
7. Reconnect services in stages, monitor for anomalies, and communicate service status through approved channels.
8. Record incident timeline, recovery point, data loss, actions, approvals, validation, notifications, and corrective work.

If restoration may reintroduce deleted or restricted student data, consult the privacy/records owner before production access is restored. Apply deletion and legal-hold decisions after recovery.

## 5. Testing and continuous improvement

Perform a documented database restore test at least quarterly and a broader application/document recovery exercise at least annually. Test point-in-time recovery and key retrieval where those capabilities are in scope. Measure actual RTO/RPO, compare with approved objectives, capture failures, assign owners and due dates, and retest corrective actions. Keep recovery instructions accessible during an outage and review them after infrastructure or schema changes.
